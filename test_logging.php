<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\LogHelper;

echo "Testing MicroNote logging system...\n";

// Test auth logging
LogHelper::authInfo('Test auth info message', [
    'email' => 'test@example.com',
    'action' => 'test_login',
]);

LogHelper::authError('Test auth error message', [
    'error_type' => 'invalid_credentials',
    'attempt_ip' => '127.0.0.1',
]);

// Test telegram logging
LogHelper::telegramInfo('Test telegram webhook received', [
    'payload_size' => 1024,
    'message_type' => 'text',
]);

LogHelper::telegramError('Test telegram API error', [
    'api_endpoint' => 'sendMessage',
    'error_code' => 400,
]);

// Test AI logging
LogHelper::aiInfo('Test AI classification started', [
    'message_length' => 50,
    'service' => 'gemini',
]);

LogHelper::aiError('Test AI API error', [
    'service' => 'gemini',
    'error' => 'quota_exceeded',
]);

// Test notes logging
LogHelper::notesInfo('Test note created', [
    'note_type' => 'simple',
    'user_id' => 1,
]);

LogHelper::notesError('Test note creation failed', [
    'validation_errors' => ['title' => 'required'],
]);

// Test jobs logging
LogHelper::jobsInfo('Test job started', [
    'job_name' => 'ProcessAndSendReminders',
    'queue' => 'default',
]);

LogHelper::jobsError('Test job failed', [
    'job_name' => 'ProcessAndSendReminders',
    'error' => 'connection_timeout',
]);

// Test system logging
LogHelper::systemWarning('Test system warning', [
    'component' => 'database',
    'issue' => 'slow_query',
]);

LogHelper::systemError('Test system error', [
    'component' => 'cache',
    'error' => 'redis_connection_failed',
]);

// Test performance logging
LogHelper::logPerformance('ai', 'test_operation', 2.5, [
    'operation_type' => 'classification',
    'success' => true,
]);

// Test exception logging
try {
    throw new Exception('Test exception for logging');
} catch (Exception $e) {
    LogHelper::logException('system', $e, [
        'context' => 'testing_logging_system',
    ]);
}

echo "Logging test completed! Check the log files in storage/logs/\n";
echo "Files to check:\n";
echo "- storage/logs/auth.log\n";
echo "- storage/logs/telegram.log\n";
echo "- storage/logs/ai.log\n";
echo "- storage/logs/notes.log\n";
echo "- storage/logs/jobs.log\n";
echo "- storage/logs/system.log\n";