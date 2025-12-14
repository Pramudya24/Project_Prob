<?php

namespace App\Http\Controllers;

use App\Models\RombonganItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SaveRombonganItemController extends Controller
{
    /**
     * Update field value dari item
     */
    public function updateField(Request $request)
    {
        try {
            $validated = $request->validate([
                'rombongan_item_id' => 'required|exists:rombongan_items,id',
                'field_name' => 'required|string',
                'field_value' => 'nullable',
            ]);

            $rombonganItem = RombonganItem::findOrFail($validated['rombongan_item_id']);
            $item = $rombonganItem->item;

            if (!$item) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Item not found'
                ], 404);
            }

            // Cek apakah field boleh diedit (tidak paten)
            $patenFields = ['nama_opd', 'tanggal_dibuat', 'id', 'created_at', 'updated_at', 'deleted_at'];
            
            if (in_array($validated['field_name'], $patenFields)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Field ini tidak dapat diedit (field paten)'
                ], 403);
            }

            // ✅ CEK: Apakah field sudah diverifikasi? Jika sudah, TIDAK BOLEH EDIT
            $verification = $rombonganItem->getFieldVerification($validated['field_name']);
            if ($verification && $verification->is_verified) {
                return response()->json([
                    'success' => false,
                    'message' => 'Field ini sudah diverifikasi dan tidak dapat diedit'
                ], 403);
            }

            // Update field value
            $item->{$validated['field_name']} = $validated['field_value'];
            $item->save();

            Log::info('Field updated', [
                'rombongan_item_id' => $validated['rombongan_item_id'],
                'field_name' => $validated['field_name'],
                'new_value' => $validated['field_value'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Field berhasil diupdate',
                'data' => [
                    'field_name' => $validated['field_name'],
                    'field_value' => $validated['field_value'],
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating field', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    } diedit (field paten)'
                ], 403);
            }

            // Update field value
            $item->{$validated['field_name']} = $validated['field_value'];
            $item->save();

            Log::info('Field updated', [
                'rombongan_item_id' => $validated['rombongan_item_id'],
                'field_name' => $validated['field_name'],
                'new_value' => $validated['field_value'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Field berhasil diupdate',
                'data' => [
                    'field_name' => $validated['field_name'],
                    'field_value' => $validated['field_value'],
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating field', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkUpdate(Request $request)
    {
        try {
            $validated = $request->validate([
                'rombongan_item_id' => 'required|exists:rombongan_items,id',
                'fields' => 'required|array',
            ]);

            $rombonganItem = RombonganItem::findOrFail($validated['rombongan_item_id']);
            $item = $rombonganItem->item;

            if (!$item) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Item not found'
                ], 404);
            }

            // Field yang tidak boleh diupdate
            $patenFields = ['nama_opd', 'tanggal_dibuat', 'id', 'created_at', 'updated_at', 'deleted_at'];

            // Update multiple fields
            $updatedFields = [];
            foreach ($validated['fields'] as $fieldName => $fieldValue) {
                // Skip field paten
                if (in_array($fieldName, $patenFields)) {
                    continue;
                }

                $item->{$fieldName} = $fieldValue;
                $updatedFields[] = $fieldName;
            }

            $item->save();

            Log::info('Bulk update completed', [
                'rombongan_item_id' => $validated['rombongan_item_id'],
                'updated_fields' => $updatedFields,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Semua field berhasil diupdate',
                'updated_fields' => $updatedFields,
            ]);

        } catch (\Exception $e) {
            Log::error('Error bulk updating', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}