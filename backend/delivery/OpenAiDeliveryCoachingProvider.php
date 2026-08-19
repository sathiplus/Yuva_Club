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
        $payload=['model'=>$this->model,'input'=>[['role'=>'system','content'=>'You are a child-safe presentation coach. Return only valid JSON.'],['role'=>'user','content'=>$prompt]],'text'=>['format'=>['type'=>'json_object']]];
        curl_setopt_array($curl,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>$this->timeoutSeconds,CURLOPT_HTTPHEADER=>['Content-Type: application/json','Authorization: Bearer '.$this->apiKey],CURLOPT_POSTFIELDS=>json_encode($payload)]);
        $raw=curl_exec($curl); $status=(int)curl_getinfo($curl,CURLINFO_RESPONSE_CODE); $failed=$raw===false||curl_errno($curl)!==0; curl_close($curl);
        if($failed||$status<200||$status>=300) return ['ok'=>false,'error_code'=>$status===408?'provider_timeout':'provider_rejected'];
        $response=json_decode((string)$raw,true); $text=(string)($response['output_text']??'');
        if($text==='') foreach(($response['output']??[]) as $item) foreach(($item['content']??[]) as $part) if(($part['type']??'')==='output_text') $text.=(string)($part['text']??'');
        $output=json_decode($text,true);
        return is_array($output)?['ok'=>true,'output'=>$output]:['ok'=>false,'error_code'=>'malformed_ai_result'];
    }
}
