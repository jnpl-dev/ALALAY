<?php

namespace App\Services;

use App\Http\Requests\Public\StoreApplicationRequest;
use App\Jobs\SendSmsJob;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ApplicationSubmissionService
{
    public function submit(StoreApplicationRequest $request, string $submissionType, ?User $encodedBy = null): Application
    {
        $referenceCode = app(ReferenceCodeService::class)->generate();

        $application = Application::create([
            'category_id' => $request->category_id,
            'reference_code' => $referenceCode,
            'status' => 'submitted',
            'submission_type' => $submissionType,
            'encoded_by' => $encodedBy?->id,
            'claimant_last_name' => $request->claimant_last_name,
            'claimant_first_name' => $request->claimant_first_name,
            'claimant_middle_name' => $request->claimant_middle_name,
            'claimant_name_extension' => $request->claimant_name_extension,
            'claimant_sex' => $request->claimant_sex,
            'claimant_dob' => $request->claimant_dob,
            'claimant_address' => $request->claimant_address,
            'claimant_phone' => $request->claimant_phone,
            'claimant_email' => $request->claimant_email,
            'claimant_relationship_to_beneficiary' => $request->claimant_relationship_to_beneficiary,
            'beneficiary_last_name' => $request->beneficiary_last_name,
            'beneficiary_first_name' => $request->beneficiary_first_name,
            'beneficiary_middle_name' => $request->beneficiary_middle_name,
            'beneficiary_name_extension' => $request->beneficiary_name_extension,
            'beneficiary_sex' => $request->beneficiary_sex,
            'beneficiary_dob' => $request->beneficiary_dob,
            'beneficiary_address' => $request->beneficiary_address,
            'beneficiary_barangay' => Application::parseBarangayFromAddress($request->beneficiary_address),
        ]);

        try {
            foreach ($request->file('documents', []) as $i => $file) {
                $requiredDocId = $request->document_ids[$i];

                $result = app(FileUploadService::class)->upload(
                    $file,
                    'application_documents',
                    $application->id,
                );

                ApplicationDocument::create([
                    'application_id' => $application->id,
                    'required_doc_id' => $requiredDocId,
                    'file_name' => $result['file_name'],
                    'file_path' => $result['file_path'],
                    'file_size' => $result['file_size'],
                    'mime_type' => $result['mime_type'],
                    'is_resubmission' => false,
                ]);
            }
        } catch (HttpException $e) {
            $application->delete();
            throw $e;
        } catch (\Exception $e) {
            $application->delete();
            Log::error('Application submission failed', [
                'error' => $e->getMessage(),
                'application_id' => $application->id,
            ]);
            throw $e;
        }

        SendSmsJob::dispatch($application, 'submission_complete');

        return $application;
    }
}