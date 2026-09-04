<?php
declare(strict_types=1);

final class GrowthProfileService
{
    public function __construct(private PDO $pdo) {}

    public function forStudent(string $yuvaId,bool $synchronizeAchievements=true):array
    {
        $student=$this->student($yuvaId);
        $evaluations=$this->evaluations((int)$student['id']);
        $skills=$this->skills($evaluations);
        $weeks=$this->qualifyingWeeks((int)$student['id']);
        $summary=[
            'leadership_level'=>(string)$student['leadership_level'],
            'challenges_completed'=>$this->count("SELECT COUNT(*) FROM dbo.quick_challenge_attempts WHERE student_id=:student AND [status]=N'Submitted'",['student'=>$student['id']]),
            'ai_coached_challenges'=>count($evaluations),
            'personal_bests'=>$this->count('SELECT COUNT(*) FROM dbo.student_challenge_personal_bests WHERE student_id=:student',['student'=>$student['id']]),
            'benchmarks_beaten'=>count(array_filter($evaluations,static fn(array $row):bool=>(int)$row['total_score']>=(int)$row['benchmark_score'])),
            'verified_presentations'=>$this->count("SELECT COUNT(*) FROM dbo.presentation_verifications WHERE student_id=:student AND [status]=N'Verified'",['student'=>$student['id']]),
            'consistency_weeks'=>$this->consecutiveWeeks($weeks),
            'practice_this_week'=>in_array((new DateTimeImmutable('now',new DateTimeZone('UTC')))->format('o-W'),$weeks,true),
        ];
        if($synchronizeAchievements)$this->synchronize((int)$student['id'],$summary,$evaluations);
        $achievements=$this->achievements((int)$student['id']);
        $trend=[];if($evaluations!==[]){$anchor=$evaluations[0];$compatible=array_values(array_filter($evaluations,static fn(array $r):bool=>(int)$r['template_id']===(int)$anchor['template_id']&&(int)$r['scoring_policy_version']===(int)$anchor['scoring_policy_version']&&(int)$r['rubric_version']===(int)$anchor['rubric_version']));$trend=array_slice(array_map(static fn(array $r):array=>['score'=>(int)$r['total_score'],'label'=>(string)$r['display_name'],'at'=>(string)$r['completed_at']],$compatible),0,5);}
        return ['student_id'=>(int)$student['id'],'yuva_id'=>(string)$student['yuva_id'],'summary'=>$summary,'skills'=>$skills,'trend'=>$trend,'personal_bests'=>$this->personalBests((int)$student['id']),'benchmarks'=>$this->benchmarks($evaluations),'achievements'=>$achievements,'ai_mentor'=>$this->aiMentorSummary((int)$student['id']),'recent_activity'=>$this->recentActivity((int)$student['id']),'next_action'=>$this->nextAction($summary,$skills,$evaluations)];
    }

    public function forParent(array $parentSession,string $yuvaId):array
    {
        $allowed=array_map(static fn(array $c):string=>strtoupper((string)($c['yuva_id']??'')),portal_parent_login_workflow()->authorizedChildren($parentSession));
        if(!in_array(strtoupper($yuvaId),$allowed,true))throw new RuntimeException('Linked Parent authorization is required.');
        return $this->forStudent($yuvaId);
    }

    public function forAdmin(array $actor,string $yuvaId):array
    {
        $role=(string)($actor['role']??'');
        if($role==='MasterAdmin')return $this->forStudent($yuvaId);
        if($role!=='OrganizationAdmin')throw new RuntimeException('Growth summary authorization is required.');
        $student=$this->student($yuvaId);$organization=strtoupper((string)($actor['organization_id']??''));
        $q=$this->pdo->prepare("SELECT TOP(1)1 FROM dbo.organization_student_membership_requests WHERE student_id=:student AND organization_code=:organization AND [status]=N'Active'");$q->execute(['student'=>$student['id'],'organization'=>$organization]);
        if($q->fetchColumn()===false)throw new RuntimeException('Active same-organization membership is required.');
        return $this->forStudent($yuvaId);
    }

    public function definitions(array $actor):array
    {
        if(($actor['role']??'')!=='MasterAdmin')throw new RuntimeException('Master Admin authorization is required.');
        return $this->pdo->query('SELECT achievement_code,definition_version,display_name,description,category,[status] FROM dbo.achievement_definitions ORDER BY id')->fetchAll(PDO::FETCH_ASSOC)?:[];
    }

    public function betaMetrics(array $actor):array
    {
        if(($actor['role']??'')!=='MasterAdmin')throw new RuntimeException('Master Admin metrics authorization is required.');
        $sql="SELECT
          COUNT(DISTINCT a.student_id) students_attempted,
          COUNT(DISTINCT CASE WHEN repeated.student_id IS NOT NULL THEN a.student_id END) students_repeated,
          COUNT(DISTINCT CASE WHEN improved.student_id IS NOT NULL THEN a.student_id END) students_improved,
          (SELECT COUNT(DISTINCT student_id) FROM dbo.student_challenge_personal_bests) students_with_personal_best,
          COUNT(DISTINCT CASE WHEN e.total_score>=e.benchmark_score THEN e.student_id END) students_beat_benchmark,
          COUNT(DISTINCT CASE WHEN another_week.student_id IS NOT NULL THEN a.student_id END) students_returned_another_week
        FROM dbo.quick_challenge_attempts a
        LEFT JOIN dbo.quick_challenge_evaluations e ON e.attempt_id=a.id AND e.[status]=N'Completed'
        LEFT JOIN(SELECT student_id FROM dbo.quick_challenge_attempts WHERE [status]=N'Submitted' GROUP BY student_id HAVING COUNT(*)>1) repeated ON repeated.student_id=a.student_id
        LEFT JOIN(SELECT student_id FROM dbo.quick_challenge_evaluations WHERE [status]=N'Completed' GROUP BY student_id HAVING MAX(total_score)>MIN(total_score)) improved ON improved.student_id=a.student_id
        LEFT JOIN(SELECT student_id FROM dbo.quick_challenge_evaluations WHERE [status]=N'Completed' GROUP BY student_id HAVING COUNT(DISTINCT CONCAT(YEAR(completed_at),N'-',DATEPART(isowk,completed_at)))>1) another_week ON another_week.student_id=a.student_id";
        return$this->pdo->query($sql)->fetch(PDO::FETCH_ASSOC)?:[];
    }

    public function setDefinitionStatus(array $actor,string $code,string $status):void
    {
        if(($actor['role']??'')!=='MasterAdmin'||!in_array($status,['Active','Disabled'],true))throw new RuntimeException('Master Admin achievement authority is required.');
        $q=$this->pdo->prepare("UPDATE dbo.achievement_definitions SET [status]=:status WHERE achievement_code=:code AND [status]<>N'Superseded'");$q->execute(['status'=>$status,'code'=>trim($code)]);
        $a=$this->pdo->prepare("INSERT dbo.student_achievement_audit(student_achievement_id,action_type,actor_role,actor_identifier,succeeded,metadata_json) VALUES(NULL,N'DefinitionStatusChanged',N'MasterAdmin',:actor,1,:metadata)");$a->execute(['actor'=>strtolower((string)($actor['email']??'')),'metadata'=>json_encode(['achievement_code'=>$code,'status'=>$status],JSON_THROW_ON_ERROR)]);
    }

    public function revoke(array $actor,string $achievementGuid,string $reason):void
    {
        if(($actor['role']??'')!=='MasterAdmin'||trim($reason)==='')throw new RuntimeException('Master Admin corrective authority and a reason are required.');
        Database::transaction(function(PDO $pdo)use($actor,$achievementGuid,$reason):void{$q=$pdo->prepare("SELECT id FROM dbo.student_achievements WITH(UPDLOCK,HOLDLOCK) WHERE achievement_guid=:guid AND [status]=N'Earned'");$q->execute(['guid'=>$achievementGuid]);$id=$q->fetchColumn();if($id===false)throw new RuntimeException('Earned achievement is required.');$u=$pdo->prepare("UPDATE dbo.student_achievements SET [status]=N'Revoked',corrected_by=:user_id,corrected_reason=:reason,corrected_at=SYSUTCDATETIME() WHERE id=:id AND [status]=N'Earned'");$u->execute(['user_id'=>(int)($actor['id']??0),'reason'=>trim($reason),'id'=>$id]);$a=$pdo->prepare("INSERT dbo.student_achievement_audit(student_achievement_id,action_type,actor_role,actor_identifier,succeeded,metadata_json) VALUES(:id,N'AchievementRevoked',N'MasterAdmin',:actor,1,:metadata)");$a->execute(['id'=>$id,'actor'=>strtolower((string)($actor['email']??'')),'metadata'=>json_encode(['reason'=>trim($reason)],JSON_THROW_ON_ERROR)]);},'SERIALIZABLE',true);
    }

    private function evaluations(int $studentId):array
    {
        $q=$this->pdo->prepare("SELECT TOP(50)e.id,e.total_score,e.benchmark_score,e.benchmark_label,e.scoring_policy_version,e.rubric_version,e.completed_at,t.id template_id,t.display_name,t.challenge_type,v.version_number template_version,STRING_AGG(CAST(s.skill_code AS NVARCHAR(MAX)),N',') skill_codes FROM dbo.quick_challenge_evaluations e JOIN dbo.quick_challenge_template_versions v ON v.id=e.template_version_id JOIN dbo.quick_challenge_templates t ON t.id=v.template_id LEFT JOIN dbo.quick_challenge_template_version_skills vs ON vs.template_version_id=v.id LEFT JOIN dbo.quick_challenge_skills s ON s.id=vs.skill_id WHERE e.student_id=:student AND e.[status]=N'Completed' GROUP BY e.id,e.total_score,e.benchmark_score,e.benchmark_label,e.scoring_policy_version,e.rubric_version,e.completed_at,t.id,t.display_name,t.challenge_type,v.version_number ORDER BY e.completed_at DESC");$q->execute(['student'=>$studentId]);return$q->fetchAll(PDO::FETCH_ASSOC)?:[];
    }

    private function skills(array $rows):array
    {
        $grouped=[];foreach($rows as$row){foreach(array_filter(explode(',',(string)$row['skill_codes']))as$skill){$key=$skill.'|'.$row['template_id'].'|'.$row['scoring_policy_version'].'|'.$row['rubric_version'];$grouped[$key][]=$row;}}
        $out=[];foreach($grouped as$key=>$items){$skill=explode('|',$key)[0];if(isset($out[$skill]))continue;$current=(int)$items[0]['total_score'];$previous=count($items)>1?(int)$items[1]['total_score']:null;$out[$skill]=['skill_code'=>$skill,'current'=>$current,'previous'=>$previous,'change'=>$previous===null?null:$current-$previous,'best'=>max(array_map(static fn(array $r):int=>(int)$r['total_score'],$items)),'attempts'=>count($items),'comparable'=>count($items)>1];}return array_values($out);
    }

    private function personalBests(int $studentId):array{$q=$this->pdo->prepare('SELECT TOP(20)t.display_name,pb.best_score,pb.achieved_at,a.attempt_guid FROM dbo.student_challenge_personal_bests pb JOIN dbo.quick_challenge_templates t ON t.id=pb.template_id JOIN dbo.quick_challenge_attempts a ON a.id=pb.best_attempt_id WHERE pb.student_id=:student ORDER BY pb.achieved_at DESC');$q->execute(['student'=>$studentId]);return$q->fetchAll(PDO::FETCH_ASSOC)?:[];}
    private function benchmarks(array $rows):array{return array_map(static function(array $r):array{$gap=(int)$r['benchmark_score']-(int)$r['total_score'];return['label'=>(string)$r['benchmark_label'],'score'=>(int)$r['total_score'],'target'=>(int)$r['benchmark_score'],'beaten'=>$gap<=0,'points_to_go'=>max(0,$gap)];},$rows);}
    private function achievements(int $studentId):array{$q=$this->pdo->prepare("SELECT CONVERT(NVARCHAR(36),a.achievement_guid) achievement_guid,d.achievement_code,d.display_name,d.description,d.category,a.earned_at,a.source_type,a.source_reference,a.evidence_json FROM dbo.student_achievements a JOIN dbo.achievement_definitions d ON d.id=a.achievement_definition_id WHERE a.student_id=:student AND a.[status]=N'Earned' ORDER BY a.earned_at DESC");$q->execute(['student'=>$studentId]);return$q->fetchAll(PDO::FETCH_ASSOC)?:[];}
    private function aiMentorSummary(int $studentId):array{$q=$this->pdo->prepare("SELECT (SELECT COUNT(*) FROM dbo.ai_mentor_reviews WHERE student_id=:research_student AND [status]=N'Applied') research_reviews,(SELECT COUNT(*) FROM dbo.ai_mentor_delivery_reviews WHERE student_id=:delivery_student AND [status]=N'Applied') coached_presentations");$q->execute(['research_student'=>$studentId,'delivery_student'=>$studentId]);return$q->fetch(PDO::FETCH_ASSOC)?:['research_reviews'=>0,'coached_presentations'=>0];}
    private function recentActivity(int $studentId):array{$q=$this->pdo->prepare("SELECT TOP(12) activity_type,label,event_at FROM(SELECT N'Quick Challenge' activity_type,t.display_name label,e.completed_at event_at FROM dbo.quick_challenge_evaluations e JOIN dbo.quick_challenge_template_versions v ON v.id=e.template_version_id JOIN dbo.quick_challenge_templates t ON t.id=v.template_id WHERE e.student_id=:challenge_student AND e.[status]=N'Completed' UNION ALL SELECT N'Verified Presentation',N'Presentation verified',verified_at FROM dbo.presentation_verifications WHERE student_id=:presentation_student AND [status]=N'Verified' UNION ALL SELECT N'Leadership',CONCAT(previous_level.code,N' to ',new_level.code),history.promoted_at FROM dbo.leadership_level_history history JOIN dbo.levels previous_level ON previous_level.id=history.previous_level_id JOIN dbo.levels new_level ON new_level.id=history.new_level_id WHERE history.student_id=:leadership_student UNION ALL SELECT N'AI Mentor',N'Research review applied',applied_at FROM dbo.ai_mentor_reviews WHERE student_id=:mentor_student AND [status]=N'Applied') recent WHERE event_at IS NOT NULL ORDER BY event_at DESC");$q->execute(['challenge_student'=>$studentId,'presentation_student'=>$studentId,'leadership_student'=>$studentId,'mentor_student'=>$studentId]);return$q->fetchAll(PDO::FETCH_ASSOC)?:[];}

    private function synchronize(int $studentId,array $summary,array $evaluations):void
    {
        $earned=[];if($summary['challenges_completed']>=1)$earned['first-challenge']=['QuickChallenge','count:1'];if($summary['ai_coached_challenges']>=1)$earned['first-ai-coached']=['QuickChallengeEvaluation','count:1'];if($summary['personal_bests']>=1)$earned['new-personal-best']=['PersonalBest','count:1'];if($summary['benchmarks_beaten']>=1)$earned['first-benchmark']=['Benchmark','count:1'];if($summary['challenges_completed']>=5)$earned['five-challenges']=['QuickChallenge','count:5'];if($summary['challenges_completed']>=10)$earned['ten-challenges']=['QuickChallenge','count:10'];if($summary['consistency_weeks']>=4)$earned['four-weeks-practice']=['WeeklyActivity','weeks:4'];if($summary['verified_presentations']>=1)$earned['first-verified-presentation']=['PresentationVerification','count:1'];if(in_array($summary['leadership_level'],['Speaker','Leader'],true))$earned['speaker-level']=['LeadershipLevel',$summary['leadership_level']];if($summary['leadership_level']==='Leader')$earned['leader-level']=['LeadershipLevel','Leader'];
        if($earned===[])return;
        $payload=[];foreach($earned as$code=>$source)$payload[]=['code'=>$code,'source_type'=>$source[0],'source_reference'=>$source[1]];
        Database::transaction(function(PDO $pdo)use($studentId,$payload):void{$sql="DECLARE @awarded TABLE(id BIGINT,achievement_code NVARCHAR(80),source_reference NVARCHAR(180));
          WITH requested AS(SELECT code,source_type,source_reference FROM OPENJSON(:awards) WITH(code NVARCHAR(80),source_type NVARCHAR(60),source_reference NVARCHAR(180))), latest AS(SELECT d.*,ROW_NUMBER()OVER(PARTITION BY d.achievement_code ORDER BY d.definition_version DESC) rn FROM dbo.achievement_definitions d WHERE d.[status]=N'Active')
          ,source_rows AS(SELECT requested.code,requested.source_type,requested.source_reference,latest.id definition_id FROM requested JOIN latest ON latest.achievement_code=requested.code AND latest.rn=1)
          MERGE dbo.student_achievements WITH(HOLDLOCK) AS target USING source_rows AS source ON target.student_id=:student_match AND target.achievement_definition_id=source.definition_id
          WHEN NOT MATCHED THEN INSERT(student_id,achievement_definition_id,source_type,source_reference,evidence_json) VALUES(:student_insert,source.definition_id,source.source_type,source.source_reference,CONCAT(N'{\"rule\":\"',STRING_ESCAPE(source.code,'json'),N'\",\"authoritative\":true}'))
          OUTPUT INSERTED.id,source.code,source.source_reference INTO @awarded;
          INSERT dbo.student_achievement_audit(student_achievement_id,action_type,actor_role,actor_identifier,succeeded,metadata_json) SELECT id,N'SystemAwarded',N'System',NULL,1,CONCAT(N'{\"rule\":\"',STRING_ESCAPE(achievement_code,'json'),N'\",\"source_reference\":\"',STRING_ESCAPE(source_reference,'json'),N'\"}') FROM @awarded;";$q=$pdo->prepare($sql);$q->execute(['awards'=>json_encode($payload,JSON_THROW_ON_ERROR),'student_match'=>$studentId,'student_insert'=>$studentId]);},'SERIALIZABLE',true);
    }

    private function qualifyingWeeks(int $studentId):array{$q=$this->pdo->prepare("SELECT DISTINCT CONCAT(YEAR(DATEADD(day,26-DATEPART(isowk,event_at),event_at)),N'-',RIGHT(N'0'+CONVERT(NVARCHAR(2),DATEPART(isowk,event_at)),2)) week_key FROM(SELECT completed_at event_at FROM dbo.quick_challenge_evaluations WHERE student_id=:student1 AND [status]=N'Completed' UNION ALL SELECT verified_at FROM dbo.presentation_verifications WHERE student_id=:student2 AND [status]=N'Verified' UNION ALL SELECT created_at FROM dbo.student_leadership_reflections WHERE student_id=:student3 UNION ALL SELECT applied_at FROM dbo.ai_mentor_reviews WHERE student_id=:student4 AND [status]=N'Applied' UNION ALL SELECT applied_at FROM dbo.ai_mentor_delivery_reviews WHERE student_id=:student5 AND [status]=N'Applied') activity WHERE event_at IS NOT NULL ORDER BY week_key DESC");$q->execute(['student1'=>$studentId,'student2'=>$studentId,'student3'=>$studentId,'student4'=>$studentId,'student5'=>$studentId]);return array_map('strval',$q->fetchAll(PDO::FETCH_COLUMN)?:[]);}
    private function consecutiveWeeks(array $weeks):int{if($weeks===[])return 0;$set=array_fill_keys($weeks,true);$date=new DateTimeImmutable('monday this week',new DateTimeZone('UTC'));$count=0;for($i=0;$i<104;$i++,$date=$date->modify('-7 days')){if(!isset($set[$date->format('o-W')]))break;$count++;}return$count;}
    private function nextAction(array $summary,array $skills,array $evaluations):array{foreach($skills as$skill)if(!$skill['comparable'])return['text'=>'Complete another '.str_replace('-',' ',$skill['skill_code']).' challenge to see your growth.','href'=>'portal.php#app-challenges'];foreach($this->benchmarks($evaluations)as$benchmark)if(!$benchmark['beaten'])return['text'=>$benchmark['points_to_go'].' points to beat the '.$benchmark['label'].'.','href'=>'portal.php#app-challenges'];if($skills!==[]){usort($skills,static fn($a,$b)=>$a['current']<=>$b['current']);return['text'=>'Practice '.str_replace('-',' ',$skills[0]['skill_code']).' next.','href'=>'portal.php#app-challenges'];}return['text'=>'Complete your first Quick Challenge to begin My Growth.','href'=>'portal.php#app-challenges'];}
    private function student(string $yuvaId):array{$q=$this->pdo->prepare("SELECT TOP(1)s.id,s.yuva_id,l.code leadership_level FROM dbo.students s JOIN dbo.levels l ON l.id=s.current_level_id WHERE UPPER(s.yuva_id)=UPPER(:yuva) AND s.approval_status=N'approved'");$q->execute(['yuva'=>trim($yuvaId)]);$row=$q->fetch(PDO::FETCH_ASSOC);if(!is_array($row))throw new RuntimeException('Approved student is required.');return$row;}
    private function count(string $sql,array $params):int{$q=$this->pdo->prepare($sql);$q->execute($params);return(int)$q->fetchColumn();}
}
