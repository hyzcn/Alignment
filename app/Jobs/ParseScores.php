<?php

namespace App\Jobs;

use App\Http\Controllers\CreatelinksController;
use App\Jobs\Job;
use App\Models\SuggestionConfigurations\SilkConfiguration;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Project;

class ParseScores extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $project,$user;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Project $project, $user)
    {
        $this->project = $project;
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $score = new CreatelinksController();
        $score->parseScore($this->project, $this->user);
        dispatch(new \App\Jobs\Convert($this->project, $this->user, "source"));
    }
}
