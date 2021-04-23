<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Image;
use File;

class PhotoController extends Controller
{
    public function getUploadForm()
    {
        $images = Photo::all();
        return view('welcome', compact('images'));
    }
    
    public function upload(Request $request)
    {
        $this->validate($request, [
            'image'     => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:5000',
            'caption'   => 'required'
        ]);
        $this->storeImage($request);
    }
    public function storeImage($request) 
    {
        // Get file from request
        $file = $request->file('image');
        $caption = $request->input('caption');
  
        // Get filename with extension
        $filenameWithExt = $file->getClientOriginalName();
  
        // Get file path
        $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
  
        // Remove unwanted characters
        $filename = preg_replace("/[^A-Za-z0-9 ]/", '', $filename);
        $filename = preg_replace("/\s+/", '-', $filename);
  
        // Get the original image extension
        $extension = $file->getClientOriginalExtension();
  
        // Create unique file name
        $fileNameToStore = $filename.'_'.time().'.'.$extension;
  
        // Refer image to method resizeImage
        $save = $this->resizeImage($file, $fileNameToStore, $caption);
  
        return true;
    }
    public function resizeImage($file, $fileNameToStore, $caption) 
    {
      // Resize image
      $resize = Image::make($file)->resize(600, null, function ($constraint) {
        $constraint->aspectRatio();
      })->encode('jpg');

      // Create hash value
      $hash = md5($resize->__toString());

      // Prepare qualified image name
      $image = $hash."jpg";

      // Put image to storage
      $save = Storage::put("public/photos/{$fileNameToStore}", $resize->__toString());
      
      //upload image name to database table
      $photo = Photo::create([
          'image'   => $fileNameToStore,
          'caption' => $caption
      ]);
      if($photo){
        //redirect dengan pesan sukses
        return redirect()->route('/photo')->with(['success' => 'Data Berhasil Disimpan!']);
        }else{
            //redirect dengan pesan error
            return redirect()->route('/photo')->with(['error' => 'Data Gagal Disimpan!']);
        }
      
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $photo = Photo::findOrFail($id);
        $image = Storage::disk('local')->delete('public/photos/'.$photo->image);
        $photo->delete();

        if($photo){
            return response()->json([
                'status' => 'success'
            ]);
        }else{
            return response()->json([
                'status' => 'error'
            ]);
        }
    }
}
