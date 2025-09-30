# Documentation

This directory contains all project documentation organized by type.

## 📁 Structure

```
docs/
├── sessions/           # Session-based work logs (one file per feature/session)
├── features/           # Feature-specific documentation (if needed)
└── README.md          # This file
```

## 📝 Session Files

Location: `docs/sessions/`

Each session file documents a complete work session or feature implementation:

**Naming Convention:** `YYYY-MM-DD-feature-name.md`

**Example:** `2025-09-30-asset-management-improvements.md`

### Session File Template

```markdown
# Session: YYYY-MM-DD - Feature Name

**Date:** YYYY-MM-DD
**Duration:** X hours
**Focus:** Brief description

## 🎯 Session Goals
- Goal 1
- Goal 2

## ✅ Completed Tasks
### Task 1
- Details
- Files changed
- Status

## 🧪 Testing Results
| Test | Status | Notes |
|------|--------|-------|

## 🐛 Known Issues
- Issue description

## 💡 Future Enhancements
- Enhancement ideas

## 📝 Files Modified
- List of files

---
**Session End:** YYYY-MM-DD
**Overall Status:** ✅/⚠️/❌
```

## 🎯 TODO.md

Location: `/TODO.md` (project root)

Central task list with:
- High/Medium/Low priority tasks
- Complexity and time estimates
- Known issues
- Technical debt items
- Feature requests

## 🔄 Workflow

### Starting New Work

1. **Check `TODO.md`** for pending tasks
2. **Review latest session** in `docs/sessions/`
3. **Create new session file** with today's date
4. **Document as you work**

### Completing Work

1. **Finalize session file** with all details
2. **Update `TODO.md`** (mark completed tasks)
3. **Reference session file** in commit messages if needed

### Finding Information

- **What was done?** → Check `docs/sessions/`
- **What needs doing?** → Check `TODO.md`
- **How was X implemented?** → Search session files for feature name
- **Why was Y changed?** → Check session file for that date

## 📊 Statistics

Track your progress:
```bash
# Count completed sessions
ls -1 docs/sessions/ | wc -l

# Find recent work
ls -lt docs/sessions/ | head -5
```

## 🔍 Search Tips

Find specific information:
```bash
# Find all sessions mentioning "asset management"
grep -r "asset management" docs/sessions/

# Find sessions from September 2025
ls docs/sessions/2025-09-*

# Find all TODOs related to testing
grep -i "test" TODO.md
```

---

**Maintained by:** Claude Code AI Assistant
**Updated:** 2025-09-30
