<?php

namespace app\services;

use OpenAI;

class AiService
{
    private $client;

    public function __construct()
    {
        $apiKey = $_ENV['GROQ_API_KEY'] ?? null;

        if (!$apiKey) {
            throw new \RuntimeException('GROQ_API_KEY is not configured.');
        }

        $this->client = OpenAI::factory()
            ->withApiKey($apiKey)
            ->withBaseUri('https://api.groq.com/openai/v1')
            ->make();
    }



    private function logUsage(
        $userId,
        $ticketId,
        $operation,
        $model,
        $status,
        $response = null,
        $errorMessage = null
    ) {
        $usage = new \app\models\AiUsageLog();

        $usage->user_id = $userId;
        $usage->ticket_id = $ticketId;
        $usage->operation = $operation;
        $usage->model = $model;
        $usage->status = $status;
        $usage->error_message = $errorMessage;
        $usage->created_at = date('Y-m-d H:i:s');

        if ($response) {
            $usage->prompt_tokens =
                $response->usage->promptTokens ?? null;

            $usage->completion_tokens =
                $response->usage->completionTokens ?? null;

            $usage->total_tokens =
                $response->usage->totalTokens ?? null;
        }

        $usage->save(false);
    }



    private function checkPromptInjection($subject, $description): bool
    {
        $response = $this->client->chat()->create([
            'model' => 'meta-llama/llama-prompt-guard-2-22m',

            'messages' => [
                [
                    'role' => 'user',
                    'content' =>
                        "Subject: {$subject}\n\n" .
                        "Description: {$description}"
                ],
            ],
        ]);

        $content = trim(
            $response->choices[0]->message->content ?? ''
        );

        /*
        * Prompt Guard should identify whether the
        * supplied text contains a prompt injection.
        *
        * We treat an explicit unsafe/injection response
        * as malicious.
        */
        $normalized = strtolower($content);

        if (
            str_contains($normalized, 'unsafe') ||
            str_contains($normalized, 'injection') ||
            str_contains($normalized, 'malicious')
        ) {
            return false;
        }

        return true;
    }


    public function analyzeTicket($subject, $description, $ticketId = null, $userId = null)
    {

        if (!$this->checkPromptInjection($subject, $description)) {
            throw new \RuntimeException(
                'Ticket text was rejected because it appears to contain a prompt injection.'
            );
        }

        try {
            $response = $this->client->chat()->create([
                'model' => 'openai/gpt-oss-20b',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' =>
                            'You are a support desk AI assistant. ' .
                            'Analyze the customer support ticket and return ONLY valid JSON. ' .
                            'The JSON must contain exactly three fields: "summary", "category", and "priority". ' .
                            'The summary must be concise, 1-2 sentences. ' .
                            'The category must be exactly one of: technical, billing, account, general. ' .
                            'The priority must be exactly one of: low, medium, high, critical. ' .
                            'Return only the JSON object with no markdown or explanation.'
                    ],
                    [
                        'role' => 'user',
                        'content' =>
                            "Subject: {$subject}\n\n" .
                            "Description: {$description}"
                    ],
                ],
            ]);

            $this->logUsage(
                $userId,
                $ticketId,
                'analyze',
                'openai/gpt-oss-20b',
                'success',
                $response
            );

        } catch (\Throwable $e) {

            $this->logUsage(
                $userId,
                $ticketId,
                'analyze',
                'openai/gpt-oss-20b',
                'error',
                null,
                $e->getMessage()
            );

            $message = $e->getMessage();

            if (
                str_contains($message, '429') ||
                str_contains(strtolower($message), 'rate limit') ||
                str_contains(strtolower($message), 'quota')
            ) {
                throw new \RuntimeException(
                    'AI service rate limit or quota exceeded. Please try again later.'
                );
            }

            if (
                str_contains($message, '401') ||
                str_contains(strtolower($message), 'unauthorized') ||
                str_contains(strtolower($message), 'invalid api key')
            ) {
                throw new \RuntimeException(
                    'AI service authentication failed. Please contact the administrator.'
                );
            }

            if (
                str_contains($message, '500') ||
                str_contains($message, '502') ||
                str_contains($message, '503') ||
                str_contains($message, '504')
            ) {
                throw new \RuntimeException(
                    'AI service is temporarily unavailable. Please try again later.'
                );
            }

            throw new \RuntimeException(
                'AI analysis failed. Please try again later.'
            );
        }

        $content = $response->choices[0]->message->content;

        $data = json_decode($content, true);

        if (!is_array($data)) {
            throw new \RuntimeException('AI returned invalid JSON.');
        }

        if (
            !isset($data['summary']) ||
            !isset($data['category']) ||
            !isset($data['priority'])
        ) {
            throw new \RuntimeException(
                'AI response is missing required fields.'
            );
        }

        $allowedCategories = [
            'technical',
            'billing',
            'account',
            'general',
        ];

        if (!in_array($data['category'], $allowedCategories, true)) {
            throw new \RuntimeException('AI returned an invalid category.');
        }

        $allowedPriorities = [
            'low',
            'medium',
            'high',
            'critical',
        ];

        if (!in_array($data['priority'], $allowedPriorities, true)) {
            throw new \RuntimeException('AI returned an invalid priority.');
        }

        /*
         * Deterministic priority rules.
         *
         * AI still suggests the priority, but clearly critical
         * situations are promoted to urgent by the application.
         */
        $text = strtolower($subject . ' ' . $description);

        $urgentKeywords = [
            'complete outage',
            'entire system',
            'system unavailable',
            'service unavailable',
            'complete system failure',
            'all users',
            'nobody can log in',
            'business operations blocked',
            'business completely blocked',
            'security incident',
            'data loss',
            'data breach',
            'critical outage',
        ];

        foreach ($urgentKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                $data['priority'] = 'critical';
                break;
            }
        }

        return [
            'summary' => $data['summary'],
            'category' => $data['category'],
            'priority' => $data['priority'],
        ];
    }


    public function parseTicketSearch($query, $userId = null)
    {
        try {

            $response = $this->client->chat()->create([
                'model' => 'openai/gpt-oss-20b',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' =>
                            'You are a support desk search assistant. ' .
                            'Convert the user search request into ONLY valid JSON. ' .
                            'The JSON must contain exactly these fields: ' .
                            '"status", "priority", "category", "search". ' .
                            'Each field may contain a value or null. ' .
                            'Allowed status values are: open, pending, resolved, closed. ' .
                            'Allowed priority values are: low, medium, high, critical. ' .
                            'Allowed category values are: technical, billing, account, general. ' .
                            '"search" should contain a short keyword or phrase to search in ticket subject or description. ' .
                            'If no keyword search is requested, use null. ' .
                            'If a filter is not specified, use null. ' .
                            'Return only the JSON object with no markdown or explanation.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $query
                    ],
                ],
            ]);

            $this->logUsage(
                $userId,
                null,
                'search',
                'openai/gpt-oss-20b',
                'success',
                $response
            );

        } catch (\Throwable $e) {

            $this->logUsage(
                $userId,
                null,
                'search',
                'openai/gpt-oss-20b',
                'error',
                null,
                $e->getMessage()
            );

            throw $e;
        }

        $content = trim(
            $response->choices[0]->message->content ?? ''
        );

        $data = json_decode($content, true);

        if (!is_array($data)) {
            throw new \RuntimeException(
                'AI returned invalid search filters.'
            );
        }

        $requiredFields = [
            'status',
            'priority',
            'category',
            'search',
        ];

        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $data)) {
                throw new \RuntimeException(
                    'AI search response is missing required fields.'
                );
            }
        }

        $allowedStatuses = [
            'open',
            'pending',
            'resolved',
            'closed',
        ];

        $allowedPriorities = [
            'low',
            'medium',
            'high',
            'critical',
        ];

        $allowedCategories = [
            'technical',
            'billing',
            'account',
            'general',
        ];

        if (
            $data['status'] !== null &&
            !in_array($data['status'], $allowedStatuses, true)
        ) {
            throw new \RuntimeException(
                'AI returned an invalid status filter.'
            );
        }

        if (
            $data['priority'] !== null &&
            !in_array($data['priority'], $allowedPriorities, true)
        ) {
            throw new \RuntimeException(
                'AI returned an invalid priority filter.'
            );
        }

        if (
            $data['category'] !== null &&
            !in_array($data['category'], $allowedCategories, true)
        ) {
            throw new \RuntimeException(
                'AI returned an invalid category filter.'
            );
        }

        return [
            'status' => $data['status'],
            'priority' => $data['priority'],
            'category' => $data['category'],
            'search' => $data['search'],
        ];
    }


    public function summarizeTicket($subject, $description)
    {
        $result = $this->analyzeTicket($subject, $description);

        return $result['summary'];
    }
}