<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            // Profile details (pulled from the User's Profile relationship)
            'id' => $this->profile->id,
            'user_id' => $this->profile->user_id,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'first_name' => $this->profile->first_name,
            'last_name' => $this->profile->last_name,
            'phone_number' => $this->profile->phone_number,
            'address' => $this->profile->address,
            'date_of_birth' => $this->profile->date_of_birth,
            'profile_picture' => $this->profile->profile_picture,
            'preferred_job_title' => $this->profile->preferred_job_title,
            'is_other_preferred_job_title' => $this->profile->is_other_preferred_job_title,
            'company_name' => $this->profile->company_name,
            'description' => $this->profile->description,
            'years_of_experience_in_the_industry' => $this->profile->years_of_experience_in_the_industry,
            'preferred_work_state' => $this->profile->preferred_work_state,
            'preferred_work_zipcode' => $this->profile->preferred_work_zipcode,
            'your_experience' => $this->profile->your_experience,
            'familiar_with_safety_protocols' => $this->profile->familiar_with_safety_protocols,
            'resume' => $this->profile->resume,
            'step' => $this->profile->step,
            'status' => $this->profile->status,
            'profile_type' => $this->profile->profile_type,
            'active_profile' => $this->active_profile,
            'created_at' => $this->profile->created_at,
            'updated_at' => $this->profile->updated_at,

            // Related data (pulled from the User model's relationships)
            'languages' => LanguageResource::collection($this->whenLoaded('languages')),
            'certifications' => CertificationResource::collection($this->whenLoaded('certifications')),
            'skills' => SkillResource::collection($this->whenLoaded('skills')),
            'education' => EducationResource::collection($this->whenLoaded('education')),
            'employment_history' => EmploymentHistoryResource::collection($this->whenLoaded('employmentHistory')),
            'looking_services' => LookingServiceResource::collection($this->whenLoaded('lookingServices')),
        ];
    }

}
