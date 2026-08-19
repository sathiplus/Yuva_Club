<?php
declare(strict_types=1);

namespace YuvaClub\Delivery;

final class OpenAiDeliveryCoachingProvider implements DeliveryCoachingProvider
{
    public function __construct(private readonly string $apiKey, private readonly string $model, private readonly int $timeoutSeconds = 60) {}
    public function modelName(): string { return $this->model; }
    public function providerName(): string { return 'openai'; }
    public function generate(string $prompt): array
    {
        if ($this->apiKey === '') return ['ok'=>false,'error_code'=>'provider_rejected'];
        $curl=curl_init('https://api.openai.com/v1/responses');
        if ($curl===false) return ['ok'=>false,'error_code'=>'provider_rejected'];
        $payload=['model'=>$this->model,'input'=>[['role'=>'system','content'=>'You are a child-safe presentation coach. Return only feedback supported by the supplied transcript and deterministic metrics.'],['role'=>'user','content'=>$prompt]],'text'=>['format'=>['type'=>'json_schema','name'=>'yuva_delivery_review','strict'=>true,'schema'=>$this->reviewSchema()]]];
        curl_setopt_array($curl,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>$this->timeoutSeconds,CURLOPT_HTTPHEADER=>['Content-Type: application/json','Authorization: Bearer '.$this->apiKey],CURLOPT_POSTFIELDS=>json_encode($payload)]);
        $raw=curl_exec($curl); $status=(int)curl_getinfo($curl,CURLINFO_RESPONSE_CODE); $failed=$raw===false||curl_errno($curl)!==0; curl_close($curl);
        if($failed||$status<200||$status>=300) return ['ok'=>false,'error_code'=>$status===408?'provider_timeout':'provider_rejected'];
        $response=json_decode((string)$raw,true); $text=(string)($response['output_text']??'');
        if($text==='') foreach(($response['output']??[]) as $item) foreach(($item['content']??[]) as $part) if(($part['type']??'')==='output_text') $text.=(string)($part['text']??'');
        $output=json_decode($text,true);
        return is_array($output)?['ok'=>true,'output'=>$output]:['ok'=>false,'error_code'=>'malformed_ai_result'];
    }

    /** @return array<string,mixed> */
    private function reviewSchema(): array
    {
        $score=['type'=>'integer','minimum'=>0,'maximum'=>100];
        $feedback=['type'=>'string','minLength'=>1,'maxLength'=>2400];
        $items=['type'=>'array','maxItems'=>20,'items'=>['type'=>'string','minLength'=>1,'maxLength'=>600]];
        $properties=[];
        foreach (['overall_delivery_score','pace_score','pause_control_score','clarity_score','vocal_variety_score','emphasis_score','filler_word_score'] as $field) $properties[$field]=$score;
        $properties['visual_presence_score']=['type'=>['integer','null'],'minimum'=>0,'maximum'=>100];
        foreach (['summary','pacing_feedback','pause_feedback','clarity_feedback','filler_word_feedback','visual_feedback','recommended_next_step','admin_notes'] as $field) $properties[$field]=$feedback;
        foreach (['strengths','improvements','pronunciation_practice','emphasis_opportunities'] as $field) $properties[$field]=$items;
        $properties['timecoded_coaching']=[
            'type'=>'array','maxItems'=>20,'items'=>[
                'type'=>'object','additionalProperties'=>false,
                'properties'=>[
                    'start_seconds'=>['type'=>'number','minimum'=>0],
                    'end_seconds'=>['type'=>['number','null'],'minimum'=>0],
                    'category'=>['type'=>'string','enum'=>['pace','pause','filler','clarity','pronunciation','emphasis','visual','structure']],
                    'observation'=>['type'=>'string','minLength'=>1,'maxLength'=>600],
                    'recommendation'=>['type'=>'string','minLength'=>1,'maxLength'=>600],
                    'priority'=>['type'=>'string','minLength'=>1,'maxLength'=>600],
                ],
                'required'=>['start_seconds','end_seconds','category','observation','recommendation','priority'],
            ],
        ];
        $properties['suggested_tokens']=['type'=>'integer','minimum'=>0,'maximum'=>4];
        return ['type'=>'object','additionalProperties'=>false,'properties'=>$properties,'required'=>array_keys($properties)];
    }
}
