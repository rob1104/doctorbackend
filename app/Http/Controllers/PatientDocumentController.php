<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PatientDocumentController extends Controller
{
    /**
     * Tipos MIME permitidos para la subida de archivos.
     */
    private const ALLOWED_MIMES = [
        // Imágenes
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        // Video
        'video/mp4',
        // Documentos
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    /**
     * Listar todos los documentos de un paciente.
     */
    public function index(Patient $patient)
    {
        $documents = $patient->documents()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($documents);
    }

    /**
     * Subir uno o varios archivos para un paciente.
     */
    public function store(Request $request, Patient $patient)
    {
        $request->validate([
            'files'   => 'required|array|min:1',
            'files.*' => 'required|file|max:20480', // 20MB máximo por archivo
        ]);

        $uploadedDocuments = [];
        $directory = "patients/{$patient->id}/documents";

        foreach ($request->file('files') as $file) {
            // Validar tipo MIME del archivo
            $mimeType = $file->getMimeType();

            if (!in_array($mimeType, self::ALLOWED_MIMES)) {
                continue; // Saltar archivos con tipo no permitido
            }

            // Generar nombre único para evitar colisiones
            $extension = $file->getClientOriginalExtension();
            $fileName = Str::uuid() . '.' . $extension;

            // Guardar el archivo en el disco público
            $filePath = $file->storeAs($directory, $fileName, 'public');

            // Crear el registro en la base de datos
            $document = PatientDocument::create([
                'patient_id'    => $patient->id,
                'file_name'     => $fileName,
                'original_name' => $file->getClientOriginalName(),
                'file_path'     => $filePath,
                'mime_type'     => $mimeType,
                'file_size'     => $file->getSize(),
            ]);

            $uploadedDocuments[] = $document;
        }

        if (empty($uploadedDocuments)) {
            return response()->json([
                'message' => 'No se subieron archivos. Verifica que los tipos de archivo sean válidos (imágenes, MP4, PDF, Word, Excel).'
            ], 422);
        }

        return response()->json([
            'message'   => count($uploadedDocuments) . ' archivo(s) subido(s) exitosamente.',
            'documents' => $uploadedDocuments,
        ], 201);
    }

    /**
     * Eliminar un documento específico de un paciente.
     */
    public function destroy(Patient $patient, PatientDocument $document)
    {
        // Verificar que el documento pertenece al paciente
        if ($document->patient_id !== $patient->id) {
            return response()->json([
                'message' => 'El documento no pertenece a este paciente.'
            ], 403);
        }

        // Eliminar archivo físico del disco
        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        // Eliminar registro de la base de datos
        $document->delete();

        return response()->json([
            'message' => 'Documento eliminado exitosamente.'
        ]);
    }
}
