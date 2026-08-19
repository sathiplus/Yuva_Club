<?php
declare(strict_types=1);

use YuvaClub\Delivery\DeliveryMetricsCalculator;
use YuvaClub\Delivery\DeliveryPromptCatalog;
use YuvaClub\Delivery\DeliveryReviewValidator;
use YuvaClub\Delivery\MediaUploadValidator;
use YuvaClub\Delivery\PresentationTranscript;
use YuvaClub\Delivery\PresentationMedia;
use YuvaClub\Delivery\DeliveryReviewService;
use YuvaClub\Delivery\MediaTranscriptionProvider;
use YuvaClub\Delivery\DeliveryCoachingProvider;
use YuvaClub\Delivery\OpenAiDeliveryCoachingProvider;
use YuvaClub\Delivery\OpenAiTranscriptionProvider;

foreach (['MediaTranscriptionProvider','DeliveryCoachingProvider','PresentationTranscript','PresentationMedia','DeliveryMetricsCalculator','MediaUploadValidator','DeliveryReviewValidator','DeliveryPromptCatalog','DeliveryReviewService','OpenAiDeliveryCoachingProvider','OpenAiTranscriptionProvider'] as $file) require_once __DIR__.'/../../backend/delivery/'.$file.'.php';
function phase1c(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); fwrite(STDOUT,"PASS {$message}\n"); }

$validator = new MediaUploadValidator();
phase1c($validator->validate('talk.mp4',100,UPLOAD_ERR_OK,'video/mp4',"\0\0\0\x18ftypisom")['ok'], 'MP4 signature accepted');
phase1c(($validator->validate('../talk.php',100,UPLOAD_ERR_OK,'text/x-php','<?php')['code'] ?? '') === 'unsupported_media', 'traversal/executable media rejected');
phase1c(($validator->validate('talk.mp4',MediaUploadValidator::MAX_BYTES+1,UPLOAD_ERR_OK,'video/mp4',"\0\0\0\x18ftypisom")['code'] ?? '') === 'media_too_large', 'oversized media rejected');
phase1c(($validator->validate('talk.mp4',100,UPLOAD_ERR_OK,'audio/mpeg',"\0\0\0\x18ftypisom")['code'] ?? '') === 'mime_mismatch', 'MIME mismatch rejected');
phase1c(($validator->validate('talk.mp4',100,UPLOAD_ERR_OK,'video/mp4','not-media')['code'] ?? '') === 'invalid_media', 'corrupted media rejected');
phase1c($validator->validate('talk.webm',100,UPLOAD_ERR_OK,'video/webm',"\x1A\x45\xDF\xA3")['ok'], 'WebM signature accepted');
phase1c($validator->validate('talk.mp3',100,UPLOAD_ERR_OK,'audio/mpeg','ID3demo')['ok'], 'MP3 signature accepted');
phase1c($validator->validate('talk.wav',100,UPLOAD_ERR_OK,'audio/wav','RIFFdemoWAVE')['ok'], 'WAV signature accepted');
phase1c($validator->validate('talk.m4a',100,UPLOAD_ERR_OK,'audio/mp4',"\0\0\0\x18ftypM4A ")['ok'], 'M4A signature accepted');

$transcript = new PresentationTranscript('Hello um everyone. This is a clear test.', 12, [['start'=>0,'end'=>2],['start'=>4,'end'=>7],['start'=>9,'end'=>12]], [['word'=>'um','start'=>0.8,'end'=>1.0]], 'en');
$metrics = (new DeliveryMetricsCalculator())->calculate($transcript);
phase1c($metrics['total_spoken_words'] === 8 && $metrics['filler_word_count'] === 1, 'word and filler metrics deterministic');
phase1c($metrics['pause_count'] === 2 && $metrics['longest_pause_seconds'] === 2.0, 'pause metrics deterministic');
phase1c(count($metrics['timecoded_evidence']) === 3, 'time-coded evidence deterministic');
phase1c(str_contains((new DeliveryPromptCatalog())->build($transcript,$metrics),'Never score accent or dialect'), 'fairness guard reaches prompt');

$qualityFixtures=[
    'normal pace'=>new PresentationTranscript(implode(' ',array_fill(0,130,'word')),60,[],[],'en'),
    'very fast pace'=>new PresentationTranscript(implode(' ',array_fill(0,220,'word')),60,[],[],'en'),
    'very slow pace'=>new PresentationTranscript(implode(' ',array_fill(0,70,'word')),60,[],[],'en'),
    'frequent fillers'=>new PresentationTranscript('Um I like basically think uh this is actually useful.',20,[],[],'en'),
    'long pauses'=>new PresentationTranscript('First point. Second point.',12,[['start'=>0,'end'=>2],['start'=>7,'end'=>9]],[],'en'),
    'clear deliberate delivery'=>new PresentationTranscript('Today I will explain one idea, show the evidence, and recommend one action.',15,[],[],'en'),
    'accented English'=>new PresentationTranscript('This English presentation uses my natural accent and clear evidence.',15,[],[],'en'),
    'multilingual proper nouns'=>new PresentationTranscript('Our work connects Thiruvananthapuram, Québec, and São Paulo.',15,[],[],'en'),
    'background noise'=>new PresentationTranscript('The transcript may be uncertain around this phrase.',15,[],[],'en',['signal'=>'background-noise']),
    'quiet recording'=>new PresentationTranscript('This quiet recording may need a slower clarity check.',15,[],[],'en',['signal'=>'quiet']),
];
$qualityMetrics=[];
foreach($qualityFixtures as $name=>$fixture){
    $qualityMetrics[$name]=(new DeliveryMetricsCalculator())->calculate($fixture);
    $qualityPrompt=(new DeliveryPromptCatalog())->build($fixture,$qualityMetrics[$name]);
    phase1c(str_contains($qualityPrompt,'Do not infer mental health, personality, intelligence')&&str_contains($qualityPrompt,'Treat ASR uncertainty as uncertainty'), $name.' safety prompt');
}
phase1c($qualityMetrics['very fast pace']['words_per_minute']>$qualityMetrics['normal pace']['words_per_minute']&&$qualityMetrics['normal pace']['words_per_minute']>$qualityMetrics['very slow pace']['words_per_minute'], 'pace fixtures remain objectively ordered');
phase1c($qualityMetrics['frequent fillers']['filler_word_count']===5, 'frequent-filler fixture remains deterministic');
phase1c($qualityMetrics['long pauses']['longest_pause_seconds']===5.0, 'long-pause fixture remains deterministic');
$multilingualPrompt=(new DeliveryPromptCatalog())->build($qualityFixtures['multilingual proper nouns'],$qualityMetrics['multilingual proper nouns']);
phase1c(str_contains($multilingualPrompt,'Thiruvananthapuram')&&str_contains($multilingualPrompt,'Never score accent or dialect'), 'multilingual names preserved without accent scoring');

$review = [
 'overall_delivery_score'=>80,'pace_score'=>80,'pause_control_score'=>80,'clarity_score'=>80,'vocal_variety_score'=>80,'emphasis_score'=>80,'filler_word_score'=>80,'visual_presence_score'=>null,
 'summary'=>'Good work','strengths'=>['Clear structure'],'improvements'=>['Slow slightly'],'pacing_feedback'=>'Steady pace','pause_feedback'=>'Use one more pause','clarity_feedback'=>'Clear overall','pronunciation_practice'=>[],'emphasis_opportunities'=>[],'filler_word_feedback'=>'One filler observed','visual_feedback'=>'Visual sampling unavailable','timecoded_coaching'=>[['start_seconds'=>0.8,'end_seconds'=>1.0,'category'=>'filler','observation'=>'Optional filler','recommendation'=>'Pause instead','priority'=>'low']],'recommended_next_step'=>'Rehearse once','suggested_tokens'=>4,'admin_notes'=>'Review before apply'
];
$contract = new DeliveryReviewValidator();
phase1c($contract->validate($review)['ok'], 'delivery structured contract accepted');
$review['suggested_tokens']=0;
phase1c($contract->validate($review)['ok'], 'zero tokens accepted');
$review['suggested_tokens']=4;
phase1c($contract->validate($review)['ok'], 'four tokens accepted');
$review['suggested_tokens']=5;
phase1c(!$contract->validate($review)['ok'], 'five tokens rejected');
$review['suggested_tokens']=-1;
phase1c(!$contract->validate($review)['ok'], 'negative tokens rejected');

$schemaMethod=(new ReflectionClass(OpenAiDeliveryCoachingProvider::class))->getMethod('reviewSchema');
$schema=$schemaMethod->invoke(new OpenAiDeliveryCoachingProvider('test-key','gpt-4.1-mini'));
phase1c(($schema['additionalProperties']??null)===false && ($schema['required']??[])===array_keys($schema['properties']??[]), 'provider strict schema requires exactly the validator fields');
phase1c(($schema['properties']['suggested_tokens']['minimum']??null)===0 && ($schema['properties']['suggested_tokens']['maximum']??null)===4, 'provider strict schema preserves zero-to-four token contract');
phase1c(($schema['properties']['visual_presence_score']['type']??null)===['integer','null'], 'provider strict schema preserves unavailable visual score');
$timestampMethod=(new ReflectionClass(OpenAiTranscriptionProvider::class))->getMethod('timestampGranularityFields');
$timestampFields=$timestampMethod->invoke(new OpenAiTranscriptionProvider('test-key'));
phase1c($timestampFields===['timestamp_granularities[]'=>'word'], 'transcription requests supported multipart word timestamps');
$timingMethod=(new ReflectionClass(OpenAiTranscriptionProvider::class))->getMethod('normalizeTiming');
$timing=$timingMethod->invoke(new OpenAiTranscriptionProvider('test-key'),['segments'=>[],'words'=>[['word'=>'Hello','start'=>1.0,'end'=>1.4],['word'=>'world','start'=>3.0,'end'=>3.4]]]);
phase1c(count($timing['segments'])===2 && $timing['segments_derived_from_words']===true && $timing['segments'][1]['start']===3.0, 'word timestamps deterministically supply missing timing segments');

$transcriber=new class($transcript) implements MediaTranscriptionProvider { public function __construct(private PresentationTranscript $value){} public function transcribe(string $path,string $name,string $mime):array{return ['ok'=>true,'transcript'=>$this->value];} public function providerName():string{return 'test';} public function modelName():string{return 'timed-test';} };
$coacher=new class($review) implements DeliveryCoachingProvider { public function __construct(private array $value){$this->value['suggested_tokens']=2;} public function generate(string $prompt):array{return ['ok'=>true,'output'=>$this->value];} public function providerName():string{return 'test';} public function modelName():string{return 'coach-test';} };
$service=new DeliveryReviewService($transcriber,$coacher,new DeliveryMetricsCalculator(),new DeliveryPromptCatalog(),new DeliveryReviewValidator());
$media=new PresentationMedia(__FILE__,'YC-TEST/media.mp4','media.mp4','video/mp4',123,str_repeat('a',64));
$draft=$service->analyze($media);
phase1c(($draft['status']??'')==='Draft' && strlen($draft['source_revision_hash']??'')===64, 'transcription to structured Draft chain');
phase1c(($draft['coaching_provider']??'')==='test' && ($draft['prompt_version']??'')===DeliveryPromptCatalog::VERSION && ($draft['visual_status']??'')==='Unavailable', 'provider, prompt, and unavailable visual provenance preserved');

$overDuration = new PresentationTranscript('A short transcript.', MediaUploadValidator::MAX_DURATION_SECONDS + 1, [], [], 'en');
$overTranscriber=new class($overDuration) implements MediaTranscriptionProvider { public function __construct(private PresentationTranscript $value){} public function transcribe(string $path,string $name,string $mime):array{return ['ok'=>true,'transcript'=>$this->value];} public function providerName():string{return 'test';} public function modelName():string{return 'timed-test';} };
$overResult=(new DeliveryReviewService($overTranscriber,$coacher,new DeliveryMetricsCalculator(),new DeliveryPromptCatalog(),new DeliveryReviewValidator()))->analyze($media);
phase1c(($overResult['status']??'')==='Failed' && ($overResult['error_code']??'')==='duration_too_long', 'over-duration media rejected before coaching');

$noSpeechTranscriber=new class implements MediaTranscriptionProvider { public function transcribe(string $path,string $name,string $mime):array{return ['ok'=>false,'error_code'=>'no_speech_detected'];} public function providerName():string{return 'test';} public function modelName():string{return 'timed-test';} };
$noSpeechResult=(new DeliveryReviewService($noSpeechTranscriber,$coacher,new DeliveryMetricsCalculator(),new DeliveryPromptCatalog(),new DeliveryReviewValidator()))->analyze($media);
phase1c(($noSpeechResult['status']??'')==='Failed' && ($noSpeechResult['error_code']??'')==='no_speech_detected', 'no-speech failure remains controlled');

$migration=file_get_contents(__DIR__.'/../../database/08-ai-mentor-phase-1c-delivery.azure-sql.sql');
phase1c(str_contains($migration,'ai_mentor_delivery_reviews') && str_contains($migration,'ROWVERSION') && str_contains($migration,"source_type=N'ai_mentor_delivery_review'") && str_contains($migration,'coaching_provider') && str_contains($migration,'prompt_version') && str_contains($migration,'visual_analysis_status'), 'separate delivery schema, provenance, and idempotency present');
