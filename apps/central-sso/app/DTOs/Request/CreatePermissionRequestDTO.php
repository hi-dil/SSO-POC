<?php

namespace App\DTOs\Request;

/**
 * @OA\Schema(
 *     schema="CreatePermissionRequestDTO",
 *     type="object",
 *     title="Create Permission Request DTO",
 *     description="Request DTO for creating permissions",
 *     required={"name"},
 *     @OA\Property(property="name", type="string", description="Permission name", example="Create Reports"),
 *     @OA\Property(property="slug", type="string", description="Permission slug (auto-generated if not provided)", example="reports.create"),
 *     @OA\Property(property="description", type="string", description="Permission description", example="Can create new reports"),
 *     @OA\Property(property="category", type="string", description="Permission category", example="reports")
 * )
 */
class CreatePermissionRequestDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $slug = null,
        public readonly ?string $description = null,
        public readonly ?string $category = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            slug: $data['slug'] ?? null,
            description: $data['description'] ?? null,
            category: $data['category'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'category' => $this->category,
        ];
    }
}