<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportContactRequest;
use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactController extends Controller
{
    public function index()
    {
        $categories = Category::all();
        $tags = Tag::all();

        return view('contact.index', compact('categories', 'tags'));
    }

    public function confirm(StoreContactRequest $request)
    {
        $validated = $request->validated();

        $category = Category::findOrFail($validated['category_id']);

        $tags = Tag::whereIn('id', $validated['tag_ids'] ?? [])->get();

        return view('contact.confirm', compact(
            'validated',
            'category',
            'tags'
        ));
    }

    public function store(StoreContactRequest $request)
    {
        $validated = $request->validated();

        $contact = Contact::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'gender' => $validated['gender'],
            'email' => $validated['email'],
            'tel' => $validated['tel'],
            'address' => $validated['address'],
            'building' => $validated['building'] ?? null,
            'category_id' => $validated['category_id'],
            'detail' => $validated['detail'],
        ]);

        $contact->tags()->attach($validated['tag_ids'] ?? []);

        return redirect('/thanks');
    }

    public function thanks()
    {
        return view('contact.thanks');
    }

    public function export(ExportContactRequest $request)
    {
        $query = Contact::with(['category', 'tags']);

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;

            $query->where(function ($query) use ($keyword) {
                $query->where('first_name', 'like', "%{$keyword}%")
                    ->orWhere('last_name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('gender') && $request->gender != 0) {
            $query->where('gender', $request->gender);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $contacts = $query->latest()->get();

        $response = new StreamedResponse(function () use ($contacts) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM
            fwrite($handle, "\xEF\xBB\xBF");

            // CSV header
            fputcsv($handle, [
                'ID',
                '氏名',
                '性別',
                'メール',
                '電話',
                '住所',
                '建物',
                'カテゴリ',
                '内容',
                '作成日時',
            ]);

            foreach ($contacts as $contact) {
                $gender = match ($contact->gender) {
                    1 => '男性',
                    2 => '女性',
                    3 => 'その他',
                    default => '',
                };

                fputcsv($handle, [
                    $contact->id,
                    $contact->last_name.' '.$contact->first_name,
                    $gender,
                    $contact->email,
                    $contact->tel,
                    $contact->address,
                    $contact->building,
                    $contact->category?->content ?? '',
                    $contact->detail,
                    $contact->created_at,
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set(
            'Content-Disposition',
            'attachment; filename="contacts.csv"'
        );

        return $response;
    }
}
