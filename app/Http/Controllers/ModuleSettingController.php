<?php

namespace App\Http\Controllers;

use App\Models\ModuleField;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ModuleSettingController extends Controller
{
    /**
     * Display a listing of module fields by module.
     */
    public function index(Request $request): View
    {
        $selectedModule = $request->query('module', ModuleField::MODULE_TAHFIDZ);
        if (! array_key_exists($selectedModule, ModuleField::MODULES)) {
            $selectedModule = ModuleField::MODULE_TAHFIDZ;
        }

        $fields = ModuleField::getAllFields($selectedModule);
        $modules = ModuleField::MODULES;

        return view('module_settings.index', compact('modules', 'selectedModule', 'fields'));
    }

    /**
     * Store a newly created module field in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'module' => 'required|in:'.implode(',', array_keys(ModuleField::MODULES)),
            'label' => 'required|string|max:150',
            'type' => 'required|in:text,select,number,textarea',
            'options_raw' => 'nullable|string',
            'placeholder' => 'nullable|string|max:150',
            'suffix' => 'nullable|string|max:30',
            'order_index' => 'nullable|integer',
        ]);

        $module = $validated['module'];
        $baseKey = 'custom_'.Str::slug($validated['label'], '_');
        $key = $baseKey;
        $counter = 1;
        while (ModuleField::where('module', $module)->where('key', $key)->exists()) {
            $key = $baseKey.'_'.$counter;
            $counter++;
        }

        $options = null;
        if ($validated['type'] === 'select' && ! empty($validated['options_raw'])) {
            $options = array_values(array_filter(array_map('trim', explode(',', $validated['options_raw']))));
        }

        $maxOrder = ModuleField::where('module', $module)->max('order_index') ?? 0;
        $orderIndex = ! empty($validated['order_index']) ? (int) $validated['order_index'] : ($maxOrder + 1);

        ModuleField::create([
            'module' => $module,
            'key' => $key,
            'label' => $validated['label'],
            'type' => $validated['type'],
            'options' => $options,
            'placeholder' => $validated['placeholder'] ?? null,
            'suffix' => $validated['suffix'] ?? null,
            'order_index' => $orderIndex,
            'is_active' => true,
            'is_system' => false,
        ]);

        return redirect()->route('module-settings.index', ['module' => $module])
            ->with('success', "Poin penilaian \"{$validated['label']}\" berhasil ditambahkan.");
    }

    /**
     * Update the specified module field in storage.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $field = ModuleField::findOrFail($id);

        $validated = $request->validate([
            'label' => 'required|string|max:150',
            'type' => 'required|in:text,select,number,textarea',
            'options_raw' => 'nullable|string',
            'placeholder' => 'nullable|string|max:150',
            'suffix' => 'nullable|string|max:30',
            'order_index' => 'required|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $options = null;
        if ($validated['type'] === 'select' && ! empty($validated['options_raw'])) {
            $options = array_values(array_filter(array_map('trim', explode(',', $validated['options_raw']))));
        } elseif ($field->is_system && $field->type === 'select' && empty($validated['options_raw'])) {
            $options = $field->options;
        }

        $field->update([
            'label' => $validated['label'],
            'type' => $field->is_system ? $field->type : $validated['type'], // Preserve system field types
            'options' => $options,
            'placeholder' => $validated['placeholder'] ?? null,
            'suffix' => $validated['suffix'] ?? null,
            'order_index' => (int) $validated['order_index'],
            'is_active' => $request->has('is_active') ? (bool) $request->input('is_active') : $field->is_active,
        ]);

        return redirect()->route('module-settings.index', ['module' => $field->module])
            ->with('success', "Poin penilaian \"{$field->label}\" berhasil diperbarui.");
    }

    /**
     * Toggle active state of a module field.
     */
    public function toggle(int $id): RedirectResponse
    {
        $field = ModuleField::findOrFail($id);
        $field->update(['is_active' => ! $field->is_active]);

        $statusText = $field->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('module-settings.index', ['module' => $field->module])
            ->with('success', "Poin penilaian \"{$field->label}\" berhasil {$statusText}.");
    }

    /**
     * Remove the specified custom module field.
     */
    public function destroy(int $id): RedirectResponse
    {
        $field = ModuleField::findOrFail($id);

        if ($field->is_system) {
            return back()->with('error', 'Poin penilaian bawaan sistem tidak dapat dihapus, namun dapat dinonaktifkan.');
        }

        $module = $field->module;
        $label = $field->label;
        $field->delete();

        return redirect()->route('module-settings.index', ['module' => $module])
            ->with('success', "Poin penilaian \"{$label}\" berhasil dihapus.");
    }

    /**
     * Reset default module fields.
     */
    public function reset(Request $request): RedirectResponse
    {
        $module = $request->input('module', ModuleField::MODULE_TAHFIDZ);
        ModuleField::where('module', $module)->delete();
        ModuleField::seedDefaultFields();

        return redirect()->route('module-settings.index', ['module' => $module])
            ->with('success', "Pengaturan poin modul {$module} berhasil dikembalikan ke pengaturan awal.");
    }
}
