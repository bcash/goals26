# 01. Life Areas

Life Areas are the six top-level categories that organize everything in Solas Rún. Every goal, project, task, and habit belongs to exactly one Life Area.

## Purpose

Life Areas prevent single-dimension optimization. Without them, it is easy to pour all energy into Business while Health, Family, and Creative atrophy. The six-area framework ensures intentional attention across your whole life.

## Default Life Areas

| Name     | Icon | Color   | Description                                            |
|----------|------|---------|--------------------------------------------------------|
| Creative | art  | #7C3AED | Writing, music, TV production, and all creative output |
| Business | work | #1D4ED8 | Client work, team management, revenue, and growth      |
| Health   | heart| #059669 | Physical wellness, mental health, energy, nutrition    |
| Family   | users| #D97706 | Relationships, presence, shared experiences, legacy    |
| Growth   | book | #0891B2 | Learning, skills, reading, courses, spiritual dev      |
| Finance  | money| #C9A84C | Income, expenses, savings, investments                 |

## Database Fields

**Table:** `life_areas`

| Field        | Type           | Constraints       | Purpose                     |
|--------------|----------------|-------------------|-----------------------------|
| id           | bigIncrements  | PK                | Primary key                 |
| user_id      | foreignId      | FK to users       | Tenant owner                |
| name         | string         | required          | Display name                |
| icon         | string         | default: heroicon | Icon identifier             |
| color_hex    | string(7)      | default: #C9A84C  | CSS hex color               |
| description  | text           | nullable          | Purpose description         |
| sort_order   | smallInteger   | default: 0        | Display ordering            |
| created_at   | timestamp      |                   |                             |
| updated_at   | timestamp      |                   |                             |

## Relationships

| Relation  | Type    | Target  |
|-----------|---------|---------|
| goals     | HasMany | Goal    |
| projects  | HasMany | Project |
| tasks     | HasMany | Task    |
| habits    | HasMany | Habit   |

## Filament Resource

**Navigation:** Settings > Life Areas

**Form Fields:**
- Name (required, max 255)
- Icon (text input)
- Color (color picker)
- Description (textarea)
- Sort Order (numeric)

**Table Columns:**
- Color swatch, Name, Description (truncated), Sort Order

## Seeding

Life Areas are seeded during `php artisan migrate --seed` via `LifeAreaSeeder`. The seeder uses `updateOrInsert` keyed on name, so re-running the seeder is safe.

## Customization

You can rename, recolor, and reorder Life Areas through the admin panel. The six defaults cover most use cases, but you can add more if your life has distinct domains not covered (e.g., separating "Career" from "Side Business").
