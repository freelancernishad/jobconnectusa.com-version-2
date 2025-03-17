<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            // Profile details
            'id' => $this->id,
            'user_id' => $this->user_id,
            'email' => $this->user->email,
            'email_verified_at' => $this->user->email_verified_at,
            'email_verified' => $this->user->hasVerifiedEmail(),
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone_number' => $this->phone_number,
            'address' => $this->address,
            'date_of_birth' => $this->date_of_birth,
            'profile_picture' => $this->profile_picture,
            'preferred_job_title' => $this->preferred_job_title,
            'is_other_preferred_job_title' => $this->is_other_preferred_job_title,
            'company_name' => $this->company_name,
            'description' => $this->description,
            'years_of_experience_in_the_industry' => $this->years_of_experience_in_the_industry,
            'preferred_work_state' => $this->preferred_work_state,
            'preferred_work_zipcode' => $this->preferred_work_zipcode,
            'your_experience' => $this->your_experience,
            'familiar_with_safety_protocols' => $this->familiar_with_safety_protocols,
            'resume' => $this->resume,
            'step' => $this->step,
            'status' => $this->status,
            'profile_type' => $this->profile_type,
            'active_profile' => $this->user->active_profile,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // User details
            // 'user' => [
            //     'id' => $this->user->id,
            //     'name' => $this->user->name,
            //     'email' => $this->user->email,
            //     'email_verified_at' => $this->user->email_verified_at,
            //     'created_at' => $this->user->created_at,
            //     'updated_at' => $this->user->updated_at,
            // ],

            // Related data
            'languages' => LanguageResource::collection($this->user->languages),
            'certifications' => CertificationResource::collection($this->user->certifications),
            'skills' => SkillResource::collection($this->user->skills),
            'education' => EducationResource::collection($this->user->education),
            'employment_history' => EmploymentHistoryResource::collection($this->user->employmentHistory),
            'looking_services' => LookingServiceResource::collection($this->user->lookingServices),
        ];
    }
}
