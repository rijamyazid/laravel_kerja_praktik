<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;
use App\File;
use App\Folder;
use App\Bidang;
use Alert;

class FilesController extends Controller
{
    public function create($bidangPrefix, $url_path=''){
        // return view('admin.files.create', ['role' => $role_prefix, 'url_path' => $url_path]);
        $sessions = Session::all();
        $roles = Bidang::orderBy('bidang_name', 'asc')->get();
        $folders = Folder::where('parent_path', 'public/' . $bidangPrefix)->get();
        $files = File::join('folders', 'folder_id','=', 'folders.id')
                ->where('bidang_id', '=', \Helper::getBidangByPrefix($bidangPrefix)->id)->get();
                
        return view('content.files.create', 
            ['url_path'=> $url_path, 
            'role' => $bidangPrefix,
            'sessions' => $sessions,
            'roles' => $roles,
            'folders' => $folders, 
            'files' => $files]);
    }

    public function store($bidangPrefix, $url_path='', Request $request){
        $basePath = 'public/' . $bidangPrefix;

        $this->validate($request,[
            'filenames' => 'required',
        ]);

        if($request->hasFile('filenames')){
            foreach ($request->file('filenames') as $file) {
                $storePath = Storage::putFile($basePath. '/'. $url_path, $file);

                $filename = $file->getClientOriginalName();
                // echo ($filename = $file->getClientOriginalName());
                $uuid = self::getUUID($storePath);
                // echo ($enc_filename = self::getUUID($path));
                $uploader = Session::get('user');

                if($url_path == ''){
                    $folder = Folder::where('parent_path', 'public')->where('bidang_id', \Helper::getBidangByPrefix($bidangPrefix)->id)->first();
                } else {
                    $folder = Folder::where('url_path', $url_path)->first();
                }
                
                File::create([
                    'file_uuid' => $uuid,
                    'file_name' => $filename,
                    'folder_id' => $folder['id'],
                    'admin_id' => \Helper::getAdminByUsername(Session::get('username'))->id,
                ]);
            }
        }
        
        Alert::success('File Berhasil Ditambah!');
        return redirect($bidangPrefix . '/folder/' .$url_path);
    }

    public function destroy($bidangPrefix, $uuid){
        $file = File::where('file_uuid', $uuid)->first();

        $filePath = $file->folder->parent_path . '/' . $file->folder->folder_name .'/'. $file->file_uuid;
        Storage::delete($filePath);

        $file->delete();

        Alert::warning('File Berhasil Dihapus!');
        return redirect('/' . $bidangPrefix . '/folder/' . $file->folder->url_path);
    }

    public function download($bidangPrefix, $uuid){
        $file = File::where('file_uuid', $uuid)->first();

        $filePath = $file->folder->parent_path . '/' . $file->folder->folder_name .'/'. $file->file_uuid;
        $fileName = $file->file_name;

        return Storage::download($filePath, $fileName);
    }

    private function getUUID($url){
        $split = explode('/', $url);
        return end($split);
    }
}
