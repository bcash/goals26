# 09. Weekly Reviews

Weekly Reviews are structured end-of-week assessments that measure progress across all six Life Areas. They capture wins, friction, and life-area scores to build a longitudinal picture of balance and momentum.

## Purpose

A weekly review forces you to step back from daily execution and evaluate the week as a whole. By scoring each Life Area independently, you can detect when one dimension is being neglected before it becomes a crisis. Over time, the accumulated reviews reveal seasonal patterns, burnout trajectories, and the impact of habit changes.

## Database Fields

**Table:** `weekly_reviews`

| Field            | Type          | Constraints          | Purpose                              |
|------------------|---------------|----------------------|--------------------------------------|
| id               | bigIncrements | PK                   | Primary key                          |
| user_id          | foreignId     | FK to users          | Tenant owner                         |
| week_start_date  | date          | required, Monday     | The Monday that starts the week      |
| wins             | text          | nullable             | What went well this week             |
| friction         | text          | nullable             | What caused drag or difficulty       |
| outcomes_met     | json          | nullable, array      | Array of outcomes achieved           |
| overall_score    | integer       | nullable, 1-5        | General week quality rating          |
| ai_analysis      | text          | nullable             | AI-generated review analysis         |
| next_week_focus  | text          | nullable             | Intention or priority for next week  |
| creative_score   | integer       | nullable, 1-5        | Creative life area score             |
| business_score   | integer       | nullable, 1-5        | Business life area score             |
| health_score     | integer       | nullable, 1-5        | Health life area score               |
| family_score     | integer       | nullable, 1-5        | Family life area score               |
| growth_score     | integer       | nullable, 1-5        | Growth life area score               |
| finance_score    | integer       | nullable, 1-5        | Finance life area score              |
| created_at       | timestamp     |                      |                                      |
| updated_at       | timestamp     |                      |                                      |

### Score Scale

All scores (overall and per-area) use a 1-5 integer scale:

| Score | Meaning                        |
|-------|--------------------------------|
| 1     | Poor -- major neglect or loss  |
| 2     | Below average -- fell short    |
| 3     | Adequate -- met baseline       |
| 4     | Good -- meaningful progress    |
| 5     | Excellent -- outstanding week  |

## Relationships

The WeeklyReview model has no relationships beyond the standard user ownership (`belongsTo User`).

## Filament Resource

**Navigation:** Journal > Weekly Reviews (sort 2)

### Form Layout

**Week Start Date**
- Date picker, required
- Defaults to the Monday of the current week
- Must be a Monday

**Reflection Section**

- Wins (textarea) -- What went well this week
- Friction (textarea) -- What caused difficulty or drag
- Next Week Focus (textarea) -- Primary intention for the coming week

**Life Area Scores Section**

Arranged as a 3x2 grid of select fields, each offering values 1 through 5:

| Row | Left          | Right         |
|-----|---------------|---------------|
| 1   | Creative (1-5)| Business (1-5)|
| 2   | Health (1-5)  | Family (1-5)  |
| 3   | Growth (1-5)  | Finance (1-5) |

**Overall Score**
- Select, values 1 through 5

**AI Analysis**
- Placeholder field for AI-generated review content
- Read-only when populated

### Table Columns

| Column          | Format                           | Sortable |
|-----------------|----------------------------------|----------|
| Week Start Date | `M j, Y` (e.g., Jan 6, 2025), bold | Yes   |
| Overall Score   | Displayed as asterisks (e.g., `***` for 3) | No |
| Wins            | Truncated to 60 characters       | No       |
| Next Week Focus | Truncated to 50 characters       | No       |

**Default sort:** `week_start_date` descending (most recent week first)

### Actions

- View, Edit

## Review Workflow

The weekly review is designed to be completed at the end of each week, ideally on Friday afternoon or Sunday evening. The recommended workflow:

1. **Open a new review.** The week start date defaults to the current week's Monday. Adjust if you are completing a review retroactively.

2. **Score each Life Area.** Go through each of the six areas and honestly rate your week on a 1-5 scale. Consider what you accomplished, how you felt, and whether you gave each area the attention it deserved.

3. **Record wins.** Write down two to five concrete things that went well. These can be task completions, breakthroughs, conversations, or personal milestones. Specificity matters -- "Shipped the API refactor and got positive client feedback" is better than "Good work week."

4. **Capture friction.** Note what caused difficulty. Was it external (client delays, unexpected issues) or internal (procrastination, energy, overcommitment)? Patterns in friction across multiple weeks reveal systemic problems.

5. **Set next week's focus.** Based on your scores and friction, declare one to three priorities for the coming week. This carries forward into your Monday morning planning session.

6. **Rate the overall week.** Step back and give the week a single 1-5 score. This is a gut-feel number that captures the week's gestalt beyond the individual area scores.

7. **Save the review.** The AI analysis field will be populated when AI features process the review, providing pattern detection and recommendations based on your review history.

## Reviewing Trends

Because each review captures six independent scores plus an overall rating, you can track trends over time. Consistently low scores in one area signal a need for rebalancing. A declining overall score across several weeks may indicate burnout or an unsustainable pace.

The `outcomes_met` JSON array stores which planned outcomes were actually achieved, providing a completion-rate signal alongside the subjective scores.
