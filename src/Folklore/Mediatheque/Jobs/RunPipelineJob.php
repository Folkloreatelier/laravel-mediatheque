<?php

namespace Folklore\Mediatheque\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Bus\Dispatcher;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Folklore\Mediatheque\Support\Pipeline;
use Folklore\Mediatheque\Support\Interfaces\HasFiles as HasFilesInterface;
use Folklore\Mediatheque\Contracts\Model\Pipeline as PipelineModel;
use Folklore\Mediatheque\Contracts\Model\PipelineJob as PipelineJobModel;
use Carbon\Carbon;
use Illuminate\Contracts\Logging\Log;
use Exception;

class RunPipelineJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $model;
    public $pipeline;
    public $pipelineJob;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(HasFilesInterface $model, PipelineModel $pipeline, PipelineJobModel $job)
    {
        $this->model = $model;
        $this->pipeline = $pipeline;
        $this->pipelineJob = $job;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Log $log)
    {
        $pipelineJob = $this->pipelineJob;
        $definition = $pipelineJob->definition;
        $name = $pipelineJob->name;
        $pipelineDefintion = $this->pipeline->definition;
        $jobClass = $definition['job'];
        $fromFile = array_get($definition, 'from_file');

        // Check if the file exists
        $file = $this->model->files->{$fromFile};
        if (!$file) {
            throw new Exception('File "'.$fromFile.'" is not available.');
        }

        $pipelineJob->markStarted();

        // Run the job
        $options = array_except($definition, ['from_file', 'job']);
        $job = new $jobClass($file, $options, $this->model);
        $newFile = app(Dispatcher::class)->dispatchNow($job);

        // Add files generated by the job to the model
        $name = array_get($options, 'name', null);
        $isIndexed = is_array($newFile);
        $files = !is_array($newFile) ? [$newFile] : $newFile;
        foreach ($files as $index => $file) {
            $fileHandle = $isIndexed && !is_null($name) ? $name.'.'.$index : $name;
            if (!is_null($fileHandle)) {
                $this->model->setFile($fileHandle, $newFile);
            } else {
                $this->model->addFile($newFile);
            }
        }

        $pipelineJob->markEnded();

        $this->model->load('files');
        $this->pipeline->load('jobs');

        // Check if there is jobs waiting for the files created by this job and run it
        if (isset($newFile)) {
            foreach ($this->pipeline->jobs as $job) {
                if ($job->isWaitingForFile($name)) {
                    $job->run();
                }
            }
        }

        // Check if all jobs are ended
        if ($this->pipeline->allJobsEnded()) {
            if ($this->pipeline->hasFailedJobs()) {
                $this->pipeline->markFailed();
            } else {
                $this->pipeline->markEnded();
            }
        }
    }

    /**
     * The job failed to process.
     *
     * @param  Exception  $exception
     * @return void
     */
    public function failed(Exception $exception = null)
    {
        $this->pipelineJob->markFailed($exception);
    }
}
