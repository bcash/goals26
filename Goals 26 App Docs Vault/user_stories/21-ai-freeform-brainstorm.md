# AI Freeform Brainstorm

**As a** Solas Run user, **I want** to use AI Studio for a freeform brainstorming session about business direction, **so that** I can think strategically with an AI partner and save the interaction for future reference.

## Scenario

Brian is considering pivoting his consulting focus from general web development to specialized e-commerce solutions. He wants to think through this strategically before making any changes. He navigates to the AI Studio page.

The AI Studio page is a dedicated Filament page with a clean interface: a large textarea for his prompt, a context selector (which Life Area and goals to include), and a response area. Brian selects "Business" as the context Life Area, which feeds his active business goals and recent project data to the AI.

He types his prompt: "I'm considering specializing in e-commerce development instead of general web work. I have three active web dev clients, one e-commerce project that went really well (the Acme checkout redesign), and my deferred pipeline has four e-commerce-related items from different clients. Help me think through whether this specialization makes sense, what the transition plan would look like, and what risks I need to consider."

The AiService processes this as a "freeform" interaction type. The context JSON includes Brian's active Business goals, his project list, recent done items (including the Acme checkout flow with its 18% cart abandonment reduction outcome), and the deferred items with e-commerce themes. The AI has rich context to work with.

The response comes back as a structured analysis: an assessment of the market opportunity based on his deferred pipeline patterns, a transition plan that leverages existing client relationships, a risk analysis covering revenue gaps during the transition, and specific first steps including creating a case study from the Acme checkout work.

Brian reads through the analysis and finds it valuable. He wants to save it and act on parts of it later. The AiInteraction record is automatically stored with the full prompt, response, context JSON, token count, and model used. He can find this later in AI Studio > AI History.

From the brainstorm, Brian creates two new items: a task "Write Acme checkout case study for portfolio" linked to his Business goal, and a deferred item "Launch e-commerce specialization page" with opportunity type "product-feature" and a revisit date of three months out. He captures both directly from the insights the AI provided.

## Steps

1. Navigate to the AI Studio page
2. Select "Business" as the context Life Area
3. Write a detailed freeform prompt about the specialization question
4. Submit and wait for the AI response
5. Read through the structured analysis
6. Create actionable tasks from the brainstorm insights
7. Create deferred items for longer-term ideas
8. Review the interaction later in AI History

## System Features Used

- **Pages:** AiStudio
- **Resources:** AiInteractionResource (AI History)
- **Services:** AiService (freeform chat)
- **Models:** AiInteraction, Task, DeferredItem
- **Context:** Goals, Projects, DeferredItems, MeetingDoneItems

## Expected Outcome

Brian has had a structured strategic thinking session with AI that drew on his real project data, client outcomes, and deferred pipeline. The interaction is saved for future reference, and concrete next steps have been captured as tasks and deferred items. The brainstorm bridges the gap between strategic thinking and daily execution within the same system.
