<?php

namespace Tests\Feature;

use App\Models\Headline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeadlineControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_headlines_all(): void
    {
        Headline::factory()->state([
            'title' => 'title-1',
            'category' => 'book-digital',
            'description' => 'description-1',
        ])->create();
        Headline::factory()->state([
            'title' => 'title-2',
            'category' => 'sound-cd',
            'description' => 'description-2',
        ])->create();

        $response = $this->getJson('/api/headlines');

        $response->assertStatus(200);
        $response->assertJsonPath('headlines.0.title', 'title-1');
        $response->assertJsonPath('headlines.0.category', 'book-digital');
        $response->assertJsonPath('headlines.0.description', 'description-1');
        $response->assertJsonPath('headlines.1.title', 'title-2');
        $response->assertJsonPath('headlines.1.category', 'sound-cd');
        $response->assertJsonPath('headlines.1.description', 'description-2');
    }

    public function test_get_headlines_all_having_refs(): void
    {
        $headline1 = Headline::factory()->state([
            'title' => 'title-1',
            'category' => 'book-digital',
            'description' => 'description-1',
        ])->create();
        $headline2 = Headline::factory()->state([
            'title' => 'title-2',
            'category' => 'sound-cd',
            'description' => 'description-2',
        ])
            ->hasAttached([$headline1], [], 'forwardRefs')
            ->create();

        $response = $this->getJson('/api/headlines');

        $response->assertStatus(200);
        $response->assertJsonPath('headlines.0.backward_refs.0.id', $headline2->id);
        $response->assertJsonPath('headlines.1.forward_refs.0.id', $headline1->id);
    }

    public function test_get_headlines_all_filtered_by_one_category(): void
    {
        Headline::factory()->state([
            'title' => 'title-1',
            'category' => 'book-digital',
            'description' => 'description-1',
        ])->create();
        Headline::factory()->state([
            'title' => 'title-2',
            'category' => 'sound-cd',
            'description' => 'description-2',
        ])->create();

        $response = $this->getJson('/api/headlines?categories=sound-cd');

        $response->assertStatus(200);
        $response->assertJsonPath('headlines.0.title', 'title-2');
        $response->assertJsonPath('headlines.0.category', 'sound-cd');
        $response->assertJsonPath('headlines.0.description', 'description-2');
        $response->assertJsonCount(1, 'headlines');
    }

    public function test_get_headlines_all_filtered_by_multiple_categories(): void
    {
        Headline::factory()->state([
            'title' => 'title-1',
            'category' => 'book-digital',
            'description' => 'description-1',
        ])->create();
        Headline::factory()->state([
            'title' => 'title-2',
            'category' => 'sound-cd',
            'description' => 'description-2',
        ])->create();
        Headline::factory()->state([
            'title' => 'title-3',
            'category' => 'sound-file',
            'description' => 'description-3',
        ])->create();

        $response = $this->getJson('/api/headlines?categories=sound-cd,sound-file');

        $response->assertStatus(200);
        $response->assertJsonPath('headlines.0.title', 'title-2');
        $response->assertJsonPath('headlines.0.category', 'sound-cd');
        $response->assertJsonPath('headlines.0.description', 'description-2');
        $response->assertJsonPath('headlines.1.title', 'title-3');
        $response->assertJsonPath('headlines.1.category', 'sound-file');
        $response->assertJsonPath('headlines.1.description', 'description-3');
        $response->assertJsonCount(2, 'headlines');
    }

    public function test_get_headlines_all_by_invalid_category(): void
    {
        Headline::factory()->state([
            'title' => 'title-1',
            'category' => 'book-digital',
            'description' => 'description-1',
        ])->create();

        $response = $this->getJson('/api/headlines?categories=invalid-category');

        $response->assertStatus(200);
        // invalid categories are ignored.
        $response->assertJsonCount(1, 'headlines');
    }

    public function test_get_headlines_one(): void
    {
        $headline = Headline::factory()->state([
            'title' => 'title-1',
            'category' => 'sound-cd',
            'description' => 'description-1',
        ])->create();

        $response = $this->getJson("/api/headlines/{$headline->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('headline.id', $headline->id);
        $response->assertJsonPath('headline.title', 'title-1');
        $response->assertJsonPath('headline.category', 'sound-cd');
        $response->assertJsonPath('headline.description', 'description-1');
    }

    public function test_get_headlines_one_having_refs(): void
    {
        $headlineForwardRef = Headline::factory()->state([
            'title' => 'title-forward-ref',
            'category' => 'sound-cd',
            'description' => 'description-forward-ref',
        ])->create();
        $headlineBackwardRef = Headline::factory()->state([
            'title' => 'title-backward-ref',
            'category' => 'sound-file',
            'description' => 'description-backward-ref',
        ])->create();
        $headline = Headline::factory()
            ->state([
                'title' => 'title',
                'category' => 'sound-vinyl',
                'description' => 'description',
            ])
            ->hasAttached([$headlineForwardRef], [], 'forwardRefs')
            ->hasAttached([$headlineBackwardRef], [], 'backwardRefs')
            ->create();

        $response = $this->getJson("/api/headlines/{$headline->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('headline.id', $headline->id);
        $response->assertJsonPath('headline.title', 'title');
        $response->assertJsonPath('headline.category', 'sound-vinyl');
        $response->assertJsonPath('headline.description', 'description');
        $response->assertJsonPath('headline.forward_refs.0.id', $headlineForwardRef->id);
        $response->assertJsonPath('headline.backward_refs.0.id', $headlineBackwardRef->id);
    }

    public function test_store_headline(): void
    {
        $response = $this->postJson('/api/headlines', [
            'title' => 'title-1',
            'category' => 'book-digital',
            'description' => 'description-1',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('headline.title', 'title-1');
        $response->assertJsonPath('headline.category', 'book-digital');
        $response->assertJsonPath('headline.description', 'description-1');
        $this->assertDatabaseHas('headlines', [
            'title' => 'title-1',
            'category' => 'book-digital',
            'description' => 'description-1',
        ]);
    }

    public function test_store_headline_with_null_description(): void
    {
        $response = $this->postJson('/api/headlines', [
            'title' => 'title-1',
            'category' => 'book-digital',
            'description' => null,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('headline.title', 'title-1');
        $response->assertJsonPath('headline.category', 'book-digital');
        $response->assertJsonPath('headline.description', null);
        $this->assertDatabaseHas('headlines', [
            'title' => 'title-1',
            'category' => 'book-digital',
            'description' => null,
        ]);
    }

    public function test_store_headline_with_refs(): void
    {
        $headlineForwardRef = Headline::factory()->state([
            'title' => 'title-forward-ref',
            'category' => 'sound-vinyl',
            'description' => 'description-forward-ref',
        ])->create();
        $headlineBackwardRef = Headline::factory()->state([
            'title' => 'title-backward-ref',
            'category' => 'sound-file',
            'description' => 'description-backward-ref',
        ])->create();

        $response = $this->postJson('/api/headlines', [
            'title' => 'title-1',
            'category' => 'book-digital',
            'description' => 'description-1',
            'forwardRefs' => [$headlineForwardRef->id],
            'backwardRefs' => [$headlineBackwardRef->id],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('headline.title', 'title-1');
        $response->assertJsonPath('headline.forward_refs.0.title', 'title-forward-ref');
        $response->assertJsonPath('headline.backward_refs.0.title', 'title-backward-ref');
        $this->assertDatabaseHas('headlines', [
            'title' => 'title-1',
            'category' => 'book-digital',
            'description' => 'description-1',
        ]);
        $this->assertDatabaseHas('headline_headline', [
            'origin_id' => $response->json('headline.id'),
            'end_id' => $headlineForwardRef->id,
        ]);
        $this->assertDatabaseHas('headline_headline', [
            'origin_id' => $headlineBackwardRef->id,
            'end_id' => $response->json('headline.id'),
        ]);
    }

    public function test_store_headline_with_null_title(): void
    {
        $response = $this->postJson('/api/headlines', [
            'title' => null,
            'category' => 'book-digital',
            'description' => 'description-1',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title' => ['The title field is required.']]);
    }

    public function test_store_headline_with_too_long_title(): void
    {
        $response = $this->postJson('/api/headlines', [
            'title' => str_repeat('long title', 2048),
            'category' => 'book-digital',
            'description' => 'description-1',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title' => ['The title field must not be greater than 100 characters.']]);
    }

    public function test_store_headline_with_invalid_category(): void
    {
        $response = $this->postJson('/api/headlines', [
            'title' => 'title-1',
            'category' => 'invalid-category',
            'description' => 'description-1',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['category' => ['The selected category is invalid.']]);
    }

    public function test_update_headline(): void
    {
        $headline = Headline::factory()->state([
            'title' => 'title-1',
            'category' => 'sound-vinyl',
            'description' => 'description-1',
        ])->create();

        $response = $this->putJson("/api/headlines/{$headline->id}", [
            'title' => 'title-2',
            'category' => 'sound-cd',
            'description' => 'description-2',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('headline.id', $headline->id);
        $response->assertJsonPath('headline.title', 'title-2');
        $response->assertJsonPath('headline.category', 'sound-cd');
        $response->assertJsonPath('headline.description', 'description-2');
        $this->assertDatabaseHas('headlines', [
            'title' => 'title-2',
            'category' => 'sound-cd',
            'description' => 'description-2',
        ]);
    }

    public function test_update_headline_with_null_description(): void
    {
        $headline = Headline::factory()->state([
            'title' => 'title-1',
            'category' => 'sound-vinyl',
            'description' => 'description-1',
        ])->create();

        $response = $this->putJson("/api/headlines/{$headline->id}", [
            'title' => 'title-2',
            'category' => 'sound-cd',
            'description' => null,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('headline.id', $headline->id);
        $response->assertJsonPath('headline.title', 'title-2');
        $response->assertJsonPath('headline.category', 'sound-cd');
        $response->assertJsonPath('headline.description', null);

        $this->assertDatabaseHas('headlines', [
            'title' => 'title-2',
            'category' => 'sound-cd',
            'description' => null,
        ]);
    }

    public function test_update_headline_with_refs(): void
    {
        $headlineForwardRef = Headline::factory()->state([
            'title' => 'title-forward-ref',
            'category' => 'sound-vinyl',
            'description' => 'description-forward-ref',
        ])->create();
        $headlineBackwardRef = Headline::factory()->state([
            'title' => 'title-backward-ref',
            'category' => 'sound-file',
            'description' => 'description-backward-ref',
        ])->create();
        $headline = Headline::factory()->state([
            'title' => 'title-1',
            'category' => 'sound-vinyl',
            'description' => 'description-1',
        ])->create();

        $response = $this->putJson("/api/headlines/{$headline->id}", [
            'title' => 'title-2',
            'category' => 'sound-cd',
            'description' => 'description-2',
            'forwardRefs' => [$headlineForwardRef->id],
            'backwardRefs' => [$headlineBackwardRef->id],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('headline.id', $headline->id);
        $response->assertJsonPath('headline.title', 'title-2');
        $response->assertJsonPath('headline.forward_refs.0.id', $headlineForwardRef->id);
        $response->assertJsonPath('headline.backward_refs.0.id', $headlineBackwardRef->id);
        $this->assertDatabaseHas('headlines', [
            'title' => 'title-2',
            'category' => 'sound-cd',
            'description' => 'description-2',
        ]);
        $this->assertDatabaseHas('headline_headline', [
            'origin_id' => $response->json('headline.id'),
            'end_id' => $headlineForwardRef->id,
        ]);
        $this->assertDatabaseHas('headline_headline', [
            'origin_id' => $headlineBackwardRef->id,
            'end_id' => $response->json('headline.id'),
        ]);
    }

    public function test_update_headline_with_null_title(): void
    {
        $headline = Headline::factory()->state([
            'title' => 'title-1',
            'category' => 'sound-vinyl',
            'description' => 'description-1',
        ])->create();

        $response = $this->putJson("/api/headlines/{$headline->id}", [
            'title' => null,
            'category' => 'sound-cd',
            'description' => 'description-2',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title' => ['The title field is required.']]);
        $this->assertDatabaseCount('headlines', 1);
        $this->assertDatabaseHas('headlines', [
            'title' => 'title-1',
            'category' => 'sound-vinyl',
            'description' => 'description-1',
        ]);
    }

    public function test_update_headline_with_too_long_title(): void
    {
        $headline = Headline::factory()->state([
            'title' => 'title-1',
            'category' => 'sound-vinyl',
            'description' => 'description-1',
        ])->create();

        $response = $this->putJson("/api/headlines/{$headline->id}", [
            'title' => str_repeat('long title', 2048),
            'category' => 'sound-cd',
            'description' => 'description-2',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title' => ['The title field must not be greater than 100 characters.']]);
        $this->assertDatabaseCount('headlines', 1);
        $this->assertDatabaseHas('headlines', [
            'title' => 'title-1',
            'category' => 'sound-vinyl',
            'description' => 'description-1',
        ]);
    }

    public function test_update_headline_with_invalid_category(): void
    {
        $headline = Headline::factory()->state([
            'title' => 'title-1',
            'category' => 'sound-vinyl',
            'description' => 'description-1',
        ])->create();

        $response = $this->putJson("/api/headlines/{$headline->id}", [
            'title' => 'title-2',
            'category' => 'invalid-category',
            'description' => 'description-2',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['category' => ['The selected category is invalid.']]);
        $this->assertDatabaseCount('headlines', 1);
        $this->assertDatabaseHas('headlines', [
            'title' => 'title-1',
            'category' => 'sound-vinyl',
            'description' => 'description-1',
        ]);
    }

    public function test_update_headline_with_duplicated_forward_refs(): void
    {
        $headlineForwardRef = Headline::factory()->state([
            'title' => 'title-forward-ref',
            'category' => 'sound-vinyl',
            'description' => 'description-forward-ref',
        ])->create();
        $headlineBackwardRef = Headline::factory()->state([
            'title' => 'title-backward-ref',
            'category' => 'sound-file',
            'description' => 'description-backward-ref',
        ])->create();
        $headline = Headline::factory()->state([
            'title' => 'title-1',
            'category' => 'sound-vinyl',
            'description' => 'description-1',
        ])
            ->hasAttached([$headlineForwardRef], [], 'forwardRefs')
            ->hasAttached([$headlineBackwardRef], [], 'backwardRefs')
            ->create();

        $response = $this->putJson("/api/headlines/{$headline->id}", [
            'title' => 'title-2',
            'category' => 'sound-cd',
            'description' => 'description-2',
            'forwardRefs' => [$headlineForwardRef->id],
        ]);

        $response->assertStatus(409);
        $response->assertJson(['forwardRefs' => ['forwardRefs is duplicated.']]);
        $this->assertDatabaseCount('headlines', 3);
        $this->assertDatabaseHas('headlines', [
            'id' => $headline->id,
            'title' => 'title-1',
            'category' => 'sound-vinyl',
            'description' => 'description-1',
        ]);
        $this->assertDatabaseCount('headline_headline', 2);
        $this->assertDatabaseHas('headline_headline', [
            'origin_id' => $headline->id,
            'end_id' => $headlineForwardRef->id,
        ]);
        $this->assertDatabaseHas('headline_headline', [
            'origin_id' => $headlineBackwardRef->id,
            'end_id' => $headline->id,
        ]);
    }

    public function test_update_headline_with_duplicated_backward_refs(): void
    {
        $headlineForwardRef = Headline::factory()->state([
            'title' => 'title-forward-ref',
            'category' => 'sound-vinyl',
            'description' => 'description-forward-ref',
        ])->create();
        $headlineBackwardRef = Headline::factory()->state([
            'title' => 'title-backward-ref',
            'category' => 'sound-file',
            'description' => 'description-backward-ref',
        ])->create();
        $headline = Headline::factory()->state([
            'title' => 'title-1',
            'category' => 'sound-vinyl',
            'description' => 'description-1',
        ])
            ->hasAttached([$headlineForwardRef], [], 'forwardRefs')
            ->hasAttached([$headlineBackwardRef], [], 'backwardRefs')
            ->create();

        $response = $this->putJson("/api/headlines/{$headline->id}", [
            'title' => 'title-2',
            'category' => 'sound-cd',
            'description' => 'description-2',
            'backwardRefs' => [$headlineBackwardRef->id],
        ]);

        $response->assertStatus(409);
        $response->assertJson(['backwardRefs' => ['backwardRefs is duplicated.']]);
        $this->assertDatabaseCount('headlines', 3);
        $this->assertDatabaseHas('headlines', [
            'id' => $headline->id,
            'title' => 'title-1',
            'category' => 'sound-vinyl',
            'description' => 'description-1',
        ]);
        $this->assertDatabaseCount('headline_headline', 2);
        $this->assertDatabaseHas('headline_headline', [
            'origin_id' => $headline->id,
            'end_id' => $headlineForwardRef->id,
        ]);
        $this->assertDatabaseHas('headline_headline', [
            'origin_id' => $headlineBackwardRef->id,
            'end_id' => $headline->id,
        ]);
    }

    public function test_delete_headline(): void
    {
        $headline = Headline::factory()->state([
            'title' => 'title-1',
            'category' => 'sound-vinyl',
            'description' => 'description-1',
        ])->create();

        $response = $this->deleteJson("/api/headlines/{$headline->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('headlines', ['id' => $headline->id]);
    }

    public function test_delete_headline_having_refs(): void
    {
        $headlineForwardRef = Headline::factory()->state([
            'title' => 'title-forward-ref',
            'category' => 'sound-vinyl',
            'description' => 'description-forward-ref',
        ])->create();
        $headlineBackwardRef = Headline::factory()->state([
            'title' => 'title-backward-ref',
            'category' => 'sound-file',
            'description' => 'description-backward-ref',
        ])->create();
        /** @var Headline $headline */
        $headline = Headline::factory()->state([
            'title' => 'title-1',
            'category' => 'sound-vinyl',
            'description' => 'description-1',
        ])
            ->hasAttached([$headlineForwardRef], [], 'forwardRefs')
            ->hasAttached([$headlineBackwardRef], [], 'backwardRefs')
            ->create();
        $this->assertDatabaseCount('headline_headline', 2);

        $response = $this->deleteJson("/api/headlines/{$headline->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('headlines', ['id' => $headline->id]);
        $this->assertDatabaseCount('headline_headline', 0);
    }

    public function test_delete_headline_with_invalid_id(): void
    {
        $headline = Headline::factory()->state([
            'title' => 'title-1',
            'category' => 'sound-vinyl',
            'description' => 'description-1',
        ])->create();
        $invalidId = $headline->id + 1;

        $response = $this->deleteJson("/api/headlines/$invalidId");

        $response->assertStatus(404);
        $this->assertNotSoftDeleted('headlines', ['id' => $headline->id]);
    }
}
