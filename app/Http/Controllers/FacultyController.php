<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FacultyController extends Controller
{
    public function index(): View
    {
        $faculties = Faculty::query()
            ->withCount('teachingLoads')
            ->orderBy('name')
            ->paginate(15);

        return view('faculties.index', compact('faculties'));
    }

    public function create(): View
    {
        return view('faculties.form', ['faculty' => new Faculty]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        Faculty::create($validated);

        return redirect()->route('faculties.index')->with('success', 'Faculty member added.');
    }

    public function edit(Faculty $faculty): View
    {
        return view('faculties.form', compact('faculty'));
    }

    public function update(Request $request, Faculty $faculty): RedirectResponse
    {
        $validated = $request->validate($this->rules($faculty));
        $faculty->update($validated);

        return redirect()->route('faculties.index')->with('success', 'Faculty member updated.');
    }

    public function destroy(Faculty $faculty): RedirectResponse
    {
        if ($faculty->teachingLoads()->exists()) {
            return redirect()->route('faculties.index')
                ->with('error', 'This faculty member cannot be deleted while they have teaching assignments.');
        }

        $faculty->delete();

        return redirect()->route('faculties.index')->with('success', 'Faculty member deleted.');
    }

    private function rules(?Faculty $faculty = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'employee_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('faculties', 'employee_number')->ignore($faculty?->id),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('faculties', 'email')->ignore($faculty?->id),
            ],
        ];
    }
}
