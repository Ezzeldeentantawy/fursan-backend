<?php
namespace App\Http\Controllers;

use App\Mail\ContactNotification;
use App\Models\ContactMessages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ContactController extends Controller
{
    /**
     * Display a listing of all messages (both read and unread).
     */
    public function index()
    {
        $messages = ContactMessages::latest()->get()->map(fn($msg) => $this->appendFileUrl($msg));

        return response()->json($messages);
    }
    /**
     * Display a listing of all messages (both read and unread).
     */
    public function getUnread()
    {
        $messages = ContactMessages::where('is_read', false)
            ->latest()
            ->get()
            ->map(fn($msg) => $this->appendFileUrl($msg));

        return response()->json($messages);
    }

    /**
     * Display only the read messages (is_read = true)
     */
    public function getRead()
    {
        $messages = ContactMessages::where('is_read', true)
            ->latest()
            ->get()
            ->map(fn($msg) => $this->appendFileUrl($msg));

        return response()->json($messages);
    }

    /**
     * Optional: Function to mark a message as read
     */
    public function markAsRead($id)
    {
        $message = ContactMessages::findOrFail($id);
        $message->update(['is_read' => true]);

        return response()->json([
            'message' => 'Status updated to read',
            'data' => $message
        ]);
    }

    private function appendFileUrl($message)
    {
        $message->file_url = $message->attached_file
            ? asset('storage/' . $message->attached_file)
            : null;
        return $message;
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone_number' => 'required|string|max:20',
            'attached_file' => 'nullable|file|mimes:pdf,doc,docx,jpg,png,jpeg|max:5120',
            'category' => 'required|string',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        // Handle File Upload Securely
        if ($request->hasFile('attached_file')) {
            // Stores in storage/app/public/contact_files with a unique name
            $path = $request->file('attached_file')->store('contact_files', 'public');
            $validated['attached_file'] = $path;
        }

        $contact = ContactMessages::create($validated);

        try {
            Mail::to('info@fursan.jo')->send(new ContactNotification($contact));
        } catch (\Exception $e) {
            \Log::error("SMTP Error: " . $e->getMessage());
            // We don't return error here so the DB record is still confirmed to user
        }

        return response()->json([
            'message' => 'Your message has been sent successfully!'
        ], 201);
    }

    public function newsLetterSubscribe(Request $request)
    {
        // 1. Validate only what is actually coming from the user (the email)
        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        // 2. Manually set the fields as per your requirements
        $validated['full_name'] = $request->email; // Name is equal to email
        $validated['subject'] = 'new newsletter subscription';
        $validated['message'] = 'The user with email ' . $request->email . ' has subscribed to the newsletter.';


        // 3. Create the record with the modified array
        $contact = ContactMessages::create($validated);

        try {
            Mail::to('info@fursan.jo')->send(new ContactNotification($contact));
        } catch (\Exception $e) {
            \Log::error("SMTP Error: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'Your subscription has been sent successfully!'
        ], 201);
    }

    /**
     * Remove the specified message from storage.
     */
    public function destroy($id)
    {
        $message = ContactMessages::findOrFail($id);

        // 1. Delete the physical file if it exists
        if ($message->attached_file) {
            Storage::disk('public')->delete($message->attached_file);
        }

        // 2. Delete the database record
        $message->delete();

        return response()->json([
            'message' => 'Message and associated files deleted successfully.'
        ], 200);
    }
}

