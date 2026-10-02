<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SpeechService
{
    public function configured(): bool
    {
        return filled(config('services.groq.api_key'));
    }

    public function transcribe(UploadedFile $audio): string
    {
        if (! $this->configured()) {
            throw new RuntimeException('Speech transcription is not configured.');
        }

        $response = Http::withToken(config('services.groq.api_key'))
            ->acceptJson()
            ->timeout(90)
            ->attach('file', file_get_contents($audio->getRealPath()), $audio->getClientOriginalName())
            ->post('https://api.groq.com/openai/v1/audio/transcriptions', [
                'model' => 'whisper-large-v3-turbo',
                'language' => 'en',
                'response_format' => 'json',
                'temperature' => 0,
            ]);

        if (! $response->successful()) {
            report(new RuntimeException('Groq transcription request failed with HTTP '.$response->status().'.'));
            throw new RuntimeException('The speech transcription service could not process this recording.');
        }

        $transcription = trim((string) $response->json('text'));

        if (! $transcription) {
            throw new RuntimeException('No speech was detected in the recording.');
        }

        return $transcription;
    }
}
