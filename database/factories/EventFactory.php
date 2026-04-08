<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EventFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(rand(3, 6));

        $stepPool = [
            'academico' => [
                'step_name' => 'Información Académica',
                'fields' => [
                    ['name' => 'university', 'type' => 'text', 'label' => 'Universidad', 'validation' => 'required|string|min:3'],
                    ['name' => 'major', 'type' => 'text', 'label' => 'Carrera', 'validation' => 'required|string'],
                    ['name' => 'graduation_year', 'type' => 'number', 'label' => 'Año de graduación', 'validation' => 'required|integer|min:2020|max:2035'],
                ],
            ],
            'profesional' => [
                'step_name' => 'Perfil Profesional',
                'fields' => [
                    ['name' => 'cv_url', 'type' => 'url', 'label' => 'Enlace a CV', 'validation' => 'required|url'],
                    ['name' => 'interests', 'type' => 'select', 'label' => 'Área de interés', 'options' => ['Frontend', 'Backend', 'Data Science', 'UI/UX'], 'validation' => 'required|in:Frontend,Backend,Data Science,UI/UX'],
                ],
            ],
            'registro_base' => [
                'step_name' => 'Registro de Asistente',
                'fields' => [
                    ['name' => 'company_org', 'type' => 'text', 'label' => 'Empresa u Organización', 'validation' => 'required|min:2'],
                    ['name' => 'job_title', 'type' => 'text', 'label' => 'Cargo actual', 'validation' => 'required|string'],
                    ['name' => 'tshirt_size', 'type' => 'select', 'label' => 'Talla de Camiseta', 'options' => ['S', 'M', 'L', 'XL', 'XXL'], 'validation' => 'required|in:S,M,L,XL,XXL'],
                ],
            ],
            'conocimientos' => [
                'step_name' => 'Conocimientos Previos',
                'fields' => [
                    ['name' => 'level', 'type' => 'select', 'label' => 'Nivel', 'options' => ['Principiante', 'Intermedio', 'Avanzado'], 'validation' => 'required|in:Principiante,Intermedio,Avanzado'],
                    ['name' => 'reason', 'type' => 'textarea', 'label' => '¿Por qué este curso?', 'validation' => 'required|string|min:20'],
                ],
            ],
            'networking' => [
                'step_name' => 'Social y Networking',
                'fields' => [
                    ['name' => 'linkedin_profile', 'type' => 'url', 'label' => 'Perfil de LinkedIn', 'validation' => 'required|url|regex:/linkedin\.com/'],
                    ['name' => 'topic_to_discuss', 'type' => 'text', 'label' => 'Tema de conversación', 'validation' => 'required|max:100'],
                    ['name' => 'dietary_restrictions', 'type' => 'text', 'label' => 'Alergias', 'validation' => 'nullable|string|max:255'],
                ],
            ],
            'tecnico' => [
                'step_name' => 'Entorno de Desarrollo',
                'fields' => [
                    ['name' => 'preferred_language', 'type' => 'select', 'label' => 'Lenguaje', 'options' => ['JavaScript', 'Python', 'PHP', 'Go', 'Rust'], 'validation' => 'required|in:JavaScript,Python,PHP,Go,Rust'],
                    ['name' => 'github_user', 'type' => 'text', 'label' => 'Usuario de GitHub', 'validation' => 'required|alpha_dash'],
                ],
            ],
            'invitacion' => [
                'step_name' => 'Detalles de Invitación',
                'fields' => [
                    ['name' => 'how_did_you_hear', 'type' => 'select', 'label' => 'Origen', 'options' => ['Redes Sociales', 'Email', 'Un amigo', 'Anuncio'], 'validation' => 'required|in:Redes Sociales,Email,Un amigo,Anuncio'],
                    ['name' => 'plus_one', 'type' => 'select', 'label' => 'Acompañante', 'options' => ['Sí', 'No'], 'validation' => 'required|in:Sí,No'],
                ],
            ],
        ];

        $selectedSteps = fake()->randomElements($stepPool, rand(2, 3));

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'type' => fake()->randomElement(['hackathon', 'bootcamp', 'workshop', 'conference', 'job_fair']),
            'modality' => fake()->randomElement(['online', 'in-person', 'hybrid']),
            'description' => fake()->paragraphs(2, true),
            'start_date' => $start = now()->addDays(rand(10, 60)),
            'end_date' => $start->copy()->addHours(rand(2, 48)),
            'registration_deadline' => $start->copy()->subDays(3),
            'capacity' => fake()->numberBetween(20, 150),
            'requires_approval' => fake()->boolean(40),
            'allows_teams' => fake()->boolean(50),
            'status' => 'published',
            'form_schema' => array_values($selectedSteps),
            'created_by' => User::role('admin')->inRandomOrder()->first()?->id
                ?? User::factory()->create()->assignRole('admin')->id,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
