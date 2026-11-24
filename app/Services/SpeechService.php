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

    public function transcribe(UploadedFile $audio, string $script): string
    {
        if (! $this->configured()) {
            throw new RuntimeException('Speech transcription is not configured.');
        }

        $isTagalog = $this->isTagalog($script);
        $request = [
            'model' => 'whisper-large-v3',
            'response_format' => 'json',
            'temperature' => 0,
            'prompt' => $this->prompt($script, $isTagalog),
        ];

        // Do not force English: Whisper detects the source language automatically.
        // A Tagalog hint improves recognition for Filipino reading passages.
        if ($isTagalog) {
            $request['language'] = 'tl';
        }

        $response = Http::withToken(config('services.groq.api_key'))
            ->acceptJson()
            ->timeout(90)
            ->attach('file', file_get_contents($audio->getRealPath()), $audio->getClientOriginalName())
            ->post('https://api.groq.com/openai/v1/audio/transcriptions', $request);

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

    private function isTagalog(string $script): bool
    {
        $words = preg_split('/\s+/u', mb_strtolower($script), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $markers = [
            'ang', 'mga', 'ng', 'siya', 'niya', 'niyang', 'kanyang', 'kanilang',
            'hindi', 'ako', 'ikaw', 'sila', 'kami', 'namin', 'natin', 'kanila',
            'ito', 'iyon', 'doon', 'dito', 'sapagkat', 'ngunit', 'habang',
        ];
        $hits = count(array_intersect($words, $markers));

        return $hits >= 2;
    }

    private function prompt(string $script, bool $isTagalog): string
    {
        $words = preg_split('/\s+/u', mb_strtolower($script), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $common = ['ang', 'mga', 'ng', 'siya', 'niya', 'niyang', 'kanyang', 'at', 'ay', 'sa', 'ito', 'na', 'habang'];
        $vocabulary = array_values(array_unique(array_filter($words, fn (string $word) => ! in_array($word, $common, true))));
        $vocabulary = array_slice($vocabulary, 0, 30);

        if ($isTagalog) {
            return 'Filipino/Tagalog reading passage. Transcribe only the audible words in Filipino; do not translate to English. Names and vocabulary: '.implode(', ', $vocabulary);
        }

        return 'Student reading exercise. Transcribe only audible speech in the language spoken. Names and vocabulary: '.implode(', ', $vocabulary);
    }
}
