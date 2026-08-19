<?php
declare(strict_types=1);

namespace YuvaClub\Delivery;

final class DeliveryReviewService
{
    public function __construct(
        private readonly MediaTranscriptionProvider $transcription,
        private readonly DeliveryCoachingProvider $coaching,
        private readonly DeliveryMetricsCalculator $metrics,
        private readonly DeliveryPromptCatalog $prompts,
        private readonly DeliveryReviewValidator $validator
    ) {}

    /** @return array<string,mixed> */
    public function analyze(PresentationMedia $media, ?array $sampledVisualObservations = null): array
    {
        $transcription=$this->transcription->transcribe($media->path,$media->originalName,$media->mimeType);
        if(!($transcription['ok']??false)||!(($transcription['transcript']??null) instanceof PresentationTranscript)) return ['ok'=>false,'status'=>'Failed','error_code'=>$transcription['error_code']??'transcription_failed'];
        $transcript=$transcription['transcript'];
        if($transcript->durationSeconds<=0||$transcript->durationSeconds>MediaUploadValidator::MAX_DURATION_SECONDS) return ['ok'=>false,'status'=>'Failed','error_code'=>'duration_too_long'];
        $metrics=$this->metrics->calculate($transcript);
        $generated=$this->coaching->generate($this->prompts->build($transcript,$metrics,$sampledVisualObservations));
        if(!($generated['ok']??false)||!is_array($generated['output']??null)) return ['ok'=>false,'status'=>'Failed','error_code'=>$generated['error_code']??'malformed_ai_result'];
        $validated=$this->validator->validate($generated['output']);
        if(!($validated['ok']??false)) return ['ok'=>false,'status'=>'Failed','error_code'=>'malformed_ai_result'];
        return [
            'ok'=>true,'status'=>'Draft','review'=>$validated['review'],'transcript'=>$transcript->toArray(),'metrics'=>$metrics,
            'duration_seconds'=>$transcript->durationSeconds,'source_revision_hash'=>$media->sourceRevisionHash($transcript->durationSeconds,$this->transcription->modelName()),
            'transcription_provider'=>$this->transcription->providerName(),'transcription_model'=>$this->transcription->modelName(),'coaching_model'=>$this->coaching->modelName(),
            'visual_status'=>$sampledVisualObservations===null?'Unavailable':'Sampled'
        ];
    }
}
