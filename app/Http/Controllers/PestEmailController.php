<?php

namespace App\Http\Controllers;

use App\Mail\DynamicEmail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PestEmailController extends Controller
{
    //
    public function sendDynamicEmail(Request $request)
    {
        try {
            // Validate the incoming request
            $validatedData = $request->validate([
                'user_id' => 'required|exists:users,id',
                'subject' => 'required|string|max:255',
                'file' => 'nullable|file|max:10240', // Optional file, max 10MB
                'html' => 'nullable|string',
            ]);
            
            // Fetch user details
            $user = User::findOrFail($validatedData['user_id']);

            // Prepare email data
            $emailData = [
                'subject' => $validatedData['subject'],
                'html' => $validatedData['html'],
                'user' => $user,
            ];

            // Handle file attachment if present
            $attachmentPath = null;
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                
                // Create directory if it doesn't exist
                $uploadDirectory = public_path('upload/email_attachments/');
                if (!file_exists($uploadDirectory)) {
                    mkdir($uploadDirectory, 0777, true);
                }

                // Generate unique filename
                $filename = uniqid() . '_' . $file->getClientOriginalName();
                $attachmentPath = $uploadDirectory . $filename;

                // Move the uploaded file
                $file->move($uploadDirectory, $filename);
            }

            // Send email
            Mail::to($user->email)->send(new DynamicEmail(
                $emailData['subject'], 
                $emailData['html'], 
                $attachmentPath
            ));

            // Optional: Remove the attachment after sending
            if ($attachmentPath && file_exists($attachmentPath)) {
                unlink($attachmentPath);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Email Sent Successfully',
            ]);
        }catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            Log::error('Dynamic Email Send Failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send email, '. $e->getMessage(),
            ], 500);
        }
    }
}
