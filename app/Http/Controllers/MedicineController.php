<?php

namespace App\Http\Controllers;

use App\Http\Resources\MedicineResource;
use App\Http\Resources\ResponseHelper;
use App\Models\Medicine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="⚙️ Admin Management",
 *     description="Medicine inventory management for administrators"
 * )
 *
 * @OA\Tag(
 *     name="👩‍⚕️ Doctor Services",
 *     description="Medicine access for prescriptions"
 * )
 */
class MedicineController extends Controller
{
    /**
     * @OA\Get(
     *     path="/admin/medicines",
     *     operationId="getAllMedicines",
     *     tags={"⚙️ Admin Management"},
     *     summary="Get all medicines",
     *     description="Retrieves all medicines in the hospital inventory (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Medicines retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Medicines retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(
     *                     property="medicines",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/MedicineResource")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        try {
            $medicines = Medicine::orderBy('medicine_name')->get();

            return ResponseHelper::success([
                'medicines' => MedicineResource::collection($medicines)
            ], 'Medicines retrieved successfully');
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to retrieve medicines', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/doctor/medicines",
     *     operationId="getDoctorAvailableMedicines",
     *     tags={"👩‍⚕️ Doctor Services"},
     *     summary="Get available medicines",
     *     description="Retrieves all medicines with stock > 0 for doctors to prescribe",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Available medicines retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Available medicines retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(
     *                     property="medicines",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/MedicineResource")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getAvailable()
    {
        try {
            $medicines = Medicine::where('stock', '>', 0)->orderBy('medicine_name')->get();

            return ResponseHelper::success([
                'medicines' => MedicineResource::collection($medicines)
            ], 'Available medicines retrieved successfully');
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to retrieve available medicines', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/admin/medicines",
     *     operationId="createMedicine",
     *     tags={"⚙️ Admin Management"},
     *     summary="Create new medicine",
     *     description="Creates a new medicine in the inventory (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"medicine_name", "medicine_type", "dosage", "unit", "stock"},
     *             @OA\Property(property="medicine_name", type="string", example="Amlodipine"),
     *             @OA\Property(property="medicine_type", type="string", example="tablet"),
     *             @OA\Property(property="dosage", type="string", example="5mg"),
     *             @OA\Property(property="unit", type="string", example="tablet"),
     *             @OA\Property(property="stock", type="integer", example=100),
     *             @OA\Property(property="description", type="string", example="Calcium channel blocker for hypertension")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Medicine created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Medicine created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="medicine", ref="#/components/schemas/MedicineResource")
     *             )
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'medicine_name' => 'required|string|max:255|unique:medicines',
                'medicine_type' => 'required|string|max:100',
                'dosage' => 'required|string|max:100',
                'unit' => 'required|string|max:50',
                'stock' => 'required|integer|min:0',
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error('Validation failed', 422, $validator->errors());
            }

            $medicine = Medicine::create([
                'medicine_name' => $request->medicine_name,
                'medicine_type' => $request->medicine_type,
                'dosage' => $request->dosage,
                'unit' => $request->unit,
                'stock' => $request->stock,
                'description' => $request->description,
            ]);

            return ResponseHelper::success([
                'medicine' => new MedicineResource($medicine)
            ], 'Medicine created successfully', 201);
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to create medicine', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/admin/medicines/{id}",
     *     operationId="getMedicine",
     *     tags={"⚙️ Admin Management"},
     *     summary="Get specific medicine",
     *     description="Retrieves a specific medicine by ID (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Medicine ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Medicine retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Medicine retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="medicine", ref="#/components/schemas/MedicineResource")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Medicine not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Medicine not found")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        try {
            $medicine = Medicine::find($id);

            if (!$medicine) {
                return ResponseHelper::error('Medicine not found', 404);
            }

            return ResponseHelper::success([
                'medicine' => new MedicineResource($medicine)
            ], 'Medicine retrieved successfully');
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to retrieve medicine', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/admin/medicines/{id}",
     *     operationId="updateMedicine",
     *     tags={"⚙️ Admin Management"},
     *     summary="Update medicine",
     *     description="Updates a medicine information and stock (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Medicine ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="medicine_name", type="string", example="Amlodipine"),
     *             @OA\Property(property="medicine_type", type="string", example="tablet"),
     *             @OA\Property(property="dosage", type="string", example="5mg"),
     *             @OA\Property(property="unit", type="string", example="tablet"),
     *             @OA\Property(property="stock", type="integer", example=100),
     *             @OA\Property(property="description", type="string", example="Calcium channel blocker for hypertension")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Medicine updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Medicine updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="medicine", ref="#/components/schemas/MedicineResource")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Medicine not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Medicine not found")
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $medicine = Medicine::find($id);

            if (!$medicine) {
                return ResponseHelper::error('Medicine not found', 404);
            }

            $validator = Validator::make($request->all(), [
                'medicine_name' => 'sometimes|string|max:255|unique:medicines,medicine_name,' . $id,
                'medicine_type' => 'sometimes|string|max:100',
                'dosage' => 'sometimes|string|max:100',
                'unit' => 'sometimes|string|max:50',
                'stock' => 'sometimes|integer|min:0',
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error('Validation failed', 422, $validator->errors());
            }

            $updateData = $request->only(['medicine_name', 'medicine_type', 'dosage', 'unit', 'stock', 'description']);

            foreach ($updateData as $key => $value) {
                if ($value !== null) {
                    $medicine->$key = $value;
                }
            }

            $medicine->save();

            return ResponseHelper::success([
                'medicine' => new MedicineResource($medicine)
            ], 'Medicine updated successfully');
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to update medicine', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/admin/medicines/{id}",
     *     operationId="deleteMedicine",
     *     tags={"⚙️ Admin Management"},
     *     summary="Delete medicine",
     *     description="Deletes a medicine from inventory (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Medicine ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Medicine deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Medicine deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Medicine not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Medicine not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Cannot delete medicine with existing prescriptions",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cannot delete medicine with existing prescriptions")
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        try {
            $medicine = Medicine::find($id);

            if (!$medicine) {
                return ResponseHelper::error('Medicine not found', 404);
            }

            // Check if medicine has any prescriptions
            if ($medicine->medicalRecords()->exists()) {
                return ResponseHelper::error('Cannot delete medicine with existing prescriptions', 400);
            }

            $medicine->delete();

            return ResponseHelper::success([], 'Medicine deleted successfully');
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to delete medicine', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/admin/medicines/{id}/stock",
     *     operationId="updateMedicineStock",
     *     tags={"⚙️ Admin Management"},
     *     summary="Update medicine stock",
     *     description="Updates medicine stock quantity (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Medicine ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"stock"},
     *             @OA\Property(property="stock", type="integer", example=100, description="New stock quantity"),
     *             @OA\Property(property="reason", type="string", example="Stock replenishment", description="Reason for stock update")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Stock updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Stock updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="medicine", ref="#/components/schemas/MedicineResource"),
     *                 @OA\Property(property="previous_stock", type="integer", example=50),
     *                 @OA\Property(property="new_stock", type="integer", example=100)
     *             )
     *         )
     *     )
     * )
     */
    public function updateStock(Request $request, $id)
    {
        try {
            $medicine = Medicine::find($id);

            if (!$medicine) {
                return ResponseHelper::error('Medicine not found', 404);
            }

            $validator = Validator::make($request->all(), [
                'stock' => 'required|integer|min:0',
                'reason' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error('Validation failed', 422, $validator->errors());
            }

            $previousStock = $medicine->stock;
            $medicine->stock = $request->stock;
            $medicine->save();

            return ResponseHelper::success([
                'medicine' => new MedicineResource($medicine),
                'previous_stock' => $previousStock,
                'new_stock' => $medicine->stock,
                'reason' => $request->reason,
            ], 'Stock updated successfully');
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to update stock', 500, $e->getMessage());
        }
    }
}
