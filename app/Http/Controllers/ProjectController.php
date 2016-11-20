<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use App\User;

use App\Project;

use App\File;

use App\Link;

use Auth;

class ProjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return Response
     */
    public function index()
    {
        $user = Auth::user();
        return view('myprojects',["user"=>$user]);
        
    }
    
    public function create()
    {
        $input = request()->all();
        
	$project = Project::create( $input );
        
        $source = $project->source;
      
        $target = $project->target;
        
        $source->projects()->attach($project->id);
        $target->projects()->attach($project->id);
        
        return redirect()->route('myprojects')->with('notification', 'Project created!!!');
        
        
    }
    
    public function show()
    {
        $id = request('project');
        $project = Project::find($id);
        return view('projects.edit', $project);       
    }
    
    public function update()
    {
        $input = request()->all();
        
        $project = Project::find($input['id']);
        
	$project->fill($input)->save();
        
        return redirect()->route('myprojects')->with('notification', 'Project updated!!!');
        
        
    }
    
    public function destroy(Request $request, Project $project)
    {
        $this->authorize('destroy', $project);
        
        $project_links = $project->links;
        
        foreach ($project_links as $link){
            $link->delete();
        }

        $project->delete();

        return redirect()->route('myprojects')->with('notification', 'Project Deleted!!!');
    }
}