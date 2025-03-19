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
            'id' => $this->id,
            'user_id' => optional($this->profile)->user_id,
            'username' => $this->username,
            'email' => $this->email,
            'email_verified' => $this->hasVerifiedEmail(),
            'email_verified_at' => $this->email_verified_at,
            'first_name' => optional($this->profile)->first_name,
            'last_name' => optional($this->profile)->last_name,
            'phone_number' => optional($this->profile)->phone_number,
            'address' => optional($this->profile)->address,
            'date_of_birth' => optional($this->profile)->date_of_birth,
            'profile_picture' => optional($this->profile)->profile_picture,
            'preferred_job_title' => optional($this->profile)->preferred_job_title,
            'is_other_preferred_job_title' => optional($this->profile)->is_other_preferred_job_title,
            'company_name' => optional($this->profile)->company_name,
            'description' => optional($this->profile)->description,
            'years_of_experience_in_the_industry' => optional($this->profile)->years_of_experience_in_the_industry,
            'preferred_work_state' => optional($this->profile)->preferred_work_state,
            'preferred_work_zipcode' => optional($this->profile)->preferred_work_zipcode,
            'your_experience' => optional($this->profile)->your_experience,
            'familiar_with_safety_protocols' => optional($this->profile)->familiar_with_safety_protocols,
            'resume' => optional($this->profile)->resume,
            'step' => optional($this->profile)->step ?? 1,
            'status' => optional($this->profile)->status,
            'profile_type' => optional($this->profile)->profile_type,
            'active_profile' => $this->active_profile,
            'created_at' => optional($this->profile)->created_at,
            'updated_at' => optional($this->profile)->updated_at,

            // Related data (pulled from the User model's relationships)
            'languages' => LanguageResource::collection($this->whenLoaded('languages')),
            'certifications' => CertificationResource::collection($this->whenLoaded('certifications')),
            'skills' => SkillResource::collection($this->whenLoaded('skills')),
            'education' => EducationResource::collection($this->whenLoaded('education')),
            'employment_history' => EmploymentHistoryResource::collection($this->whenLoaded('employmentHistory')),
            'looking_services' => LookingServiceResource::collection($this->whenLoaded('lookingServices')),
            'services_looking_for' => LookingServiceResource::collection($this->whenLoaded('servicesLookingFor')),
        ];
    }

}
