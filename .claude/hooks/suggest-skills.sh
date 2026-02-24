#!/bin/bash
# Hook: UserPromptSubmit
# Analyzes the user's prompt and suggests relevant skills to activate.
# Outputs skill suggestions that get injected into Claude's context.

INPUT=$(cat)
PROMPT=$(echo "$INPUT" | tr '[:upper:]' '[:lower:]')

SKILLS=()

# Laravel/Filament development
if echo "$PROMPT" | grep -qiE 'filament|resource|widget|dashboard|form|table|admin|panel|relation.?manager|navigation|badge|column|filter|action|bulk'; then
    SKILLS+=("laravel-filament-development")
fi

# PHPUnit testing
if echo "$PROMPT" | grep -qiE 'test|testing|phpunit|assert|factory|coverage|tdd|test.driven|spec|verify|feature.test|unit.test'; then
    SKILLS+=("phpunit-testing")
fi

# Development planning
if echo "$PROMPT" | grep -qiE 'plan|architect|design|breakdown|decompose|strategy|approach|implement.*feature|multi.?file|complex|refactor|restructure'; then
    SKILLS+=("development-planning")
fi

# Tailwind CSS (already exists via Boost, reinforce here)
if echo "$PROMPT" | grep -qiE 'css|style|tailwind|restyle|dark.mode|responsive|layout|grid|flex|spacing|color|theme|ui|visual'; then
    SKILLS+=("tailwindcss-development")
fi

# If skills were matched, output the suggestion
if [ ${#SKILLS[@]} -gt 0 ]; then
    SKILL_LIST=$(printf ", %s" "${SKILLS[@]}")
    SKILL_LIST=${SKILL_LIST:2}  # Remove leading ", "

    cat <<EOF

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🎯 SKILL ACTIVATION CHECK
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📚 RECOMMENDED SKILLS: ${SKILL_LIST}

ACTION: Use the Skill tool to activate these skills BEFORE responding.
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

EOF
fi

exit 0
