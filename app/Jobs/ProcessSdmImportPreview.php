<?php

namespace App\Jobs;

use App\Models\SdmImportRun;
use App\Services\SdmImportRunService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessSdmImportPreview implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 900;

    public int $tries = 1;

    public function __construct(
        public int $runId
    ) {}

    public function handle(SdmImportRunService $sdmImportRunService): void
    {
        $run = SdmImportRun::find($this->runId);
        if (! $run) {
            return;
        }

        $sdmImportRunService->processPreviewRun($run);
    }
}
