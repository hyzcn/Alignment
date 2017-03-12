<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\File;
use App\User;
use Auth;
use Cache;


class FileController extends Controller
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
    public function mygraphs()
    {
        $user = Auth::user();
        return view('mygraphs',["user"=>$user]);
    }
    
    public function store()
    {
        $input = request()->all();
	File::create( $input );
        return redirect()->route('mygraphs')->with('notification', 'File Uploaded!!!');
    }
    
    public function show()
    {
        $id = request('file');
        $file = File::find($id);
        return view('files.edit', $file);       
    }
    
    
    public function update()
    {
        $input = request()->all();
        
        $file = File::find($input['id']);
        
	$file->public = $input['public'];
        
        $file->filetype = $input['filetype'];
        
        $file->save();
        
        return redirect()->route('mygraphs')->with('notification', 'File updated!!!');
        
        
    }
    
    public function destroy(Request $request, File $file)
    {
        $this->authorize('destroy', $file);

        $file->delete();

        return redirect()->route('mygraphs')->with('notification', 'File Deleted!!!');
    }
    
    public function parse(File $file)
    {
        $graph = new \EasyRdf_Graph();
        /*
         * Read the graph
         */
        try{
          if($file->filetype != 'rdfxml'){
              logger('inserted converter');
              FileController::convert($file);
              logger('exited converter');
              
              $graph->parseFile($file->resource->path() . '.rdf', 'rdfxml');
              logger('parsing_finished');
          }
          else{
              $graph->parseFile($file->resource->path(), 'rdfxml');
          }
          logger('passed check');
          //$graph -> parseFile($file->resource->path(), 'rdfxml');
          //$_SESSION['test' . "_graph" ] = $graph;
          logger("finished parsing");
          $file->parsed = true;
          $file->save();
          return redirect()->route('mygraphs')->with('notification', 'Graph Parsed!!!');
          
        } catch (\Exception $ex) {
            $file->parsed = false;
            $file->save();
            error_log($ex);
          return redirect()->route('mygraphs')->with('error', 'Failed to parse the graph. We currently support only RDF/XML format');
        }       
    }
    
    public function convert(File $file){
        $command = 'rapper -i ' . $file->filetype . ' -o rdfxml-abbrev ' . $file->resource->path() . ' > ' . $file->resource->path(). '.rdf';  
        $out = [];
        logger($command);
        exec( $command, $out);
        logger(var_dump($out));
        return;
    }
    
    public function cacheGraph(\App\File $file){
        if(Cache::has($file->id. "_graph")){
            return;
        }
        else{
            $graph = new \EasyRdf_Graph;
            $suffix = ($file->filetype != 'rdfxml' ) ? '.rdf' : '';
            $graph->parseFile($file->resource->path() . $suffix, 'rdfxml');
            Cache::forever($file->id. "_graph", $graph);
            return;
        }
        
    }
}
