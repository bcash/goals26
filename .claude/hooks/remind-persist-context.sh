#!/bin/bash
# Hook: Stop
# Reminds Claude to save task plan and context before ending the session.
# Only triggers if the session involved meaningful work (not just questions).

INPUT=$(cat)

# Check if this was a substantial session by looking at the transcript
# The hook fires on every Stop, so we keep the message concise
cat <<'EOF'

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
💾 SESSION CONTEXT REMINDER
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

If you worked on a task during this session:

1. Save the implementation plan:
   → Use `update-task-plan` MCP tool with the task ID

2. Save the working context:
   → Use `update-task-context` MCP tool with the task ID

This ensures the next session can pick up where you left off.
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

EOF

exit 0
