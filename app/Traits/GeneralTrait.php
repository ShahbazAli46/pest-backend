<?php

namespace App\Traits;

use App\Models\Attachment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

trait GeneralTrait
{
    //add user
    public function addUser(Request $request)
    {
        try {
            $registerUserData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|unique:users|max:255',
                'role_id' => 'required|exists:roles,id',
            ]);
            $user_password=$this->generateRandomPassword(12);
            $user = User::create([
                'name' => $registerUserData['name'],
                'email' => $registerUserData['email'],
                'role_id' => $request->role_id,
                'password' => Hash::make($user_password),
            ]);

            $user_role=Role::where('id',$user->role_id)->first()->name;
            $message="Dear User This is your Password ".$user_password;
            //should be send through mail

            // if($request->image){
            //     saveImage($request->image,$user->id,'App\Models\User','users','Employee Photo');
            // }
            return ['status' => 'success','message' => 'User Created','data' => $user];
        }catch (\Illuminate\Validation\ValidationException $e) {
            return ['status'=> 'error','message' => $e->validator->errors()->first()];
        } catch (\Exception $e) {
            // Other unexpected errors
            return ['status' => 'error','message' => 'Failed to Add User. ' . $e->getMessage()];
        }
    }

    // Generating Random Password
    function generateRandomPassword($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()-_=+';
        $password = '';
        $characterCount = strlen($characters) - 1;
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[mt_rand(0, $characterCount)];
        }
        return $password;
    }


    function saveAttachments($attachments, $model_id, $model_type, $folder, $description = null)
    {
        foreach ($attachments as $attachment) {
            $name = $attachment->getClientOriginalName();
            $name = strtolower(str_replace(' ', '-', $name));
            $destinationPath = public_path() . 'upload/' . $folder . '/';
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0775, true);
            }
            $fileName = $folder . '_' . rand(0, 999) . '.' . $attachment->getClientOriginalExtension();
            $attachment->move($destinationPath, $fileName);

            $fileSize = filesize($destinationPath); 
            $file_size=$fileSize?$fileSize:0;

            // Save the attachment record in the database
            $newAttachment = new Attachment;
            $newAttachment->file_name = $name;
            $newAttachment->file_path = 'upload/' . $fileName;
            $newAttachment->file_extension = $attachment->getClientOriginalExtension();
            $newAttachment->file_size = $file_size;
            $newAttachment->attachmentable_id = $model_id;
            $newAttachment->attachmentable_type = $model_type;
            $newAttachment->attachment_description = $description;
            $newAttachment->save();
        }
    }


    function saveImage($image,$folder){
       // Generate a unique filename
        $extension = $image->getClientOriginalExtension();
        $fileName = $folder . '_' . uniqid() . '.' . $extension;
        $destinationPath = public_path('upload/' . $folder . '/');
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }
        $image->move($destinationPath, $fileName);
        return 'upload/' . $fileName;    
    }

   
    // // Example of a utility method
    // public function formatDate($date)
    // {
    //     return \Carbon\Carbon::parse($date)->format('Y-m-d');
    // }
}
