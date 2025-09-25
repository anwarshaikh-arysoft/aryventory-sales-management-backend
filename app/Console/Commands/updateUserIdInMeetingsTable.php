<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\Meeting;
use Illuminate\Console\Command;

class updateUserIdInMeetingsTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-user-id-in-meetings-table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $leads = Lead::all();
        foreach ($leads as $lead) {
            $lead->meetings->each(function ($meeting) {
                $meeting->user_id = 9;
                $meeting->save();
                echo $meeting->lead_id . "\n";
            });
        }    
    }
}
