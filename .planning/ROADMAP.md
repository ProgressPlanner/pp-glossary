# Roadmap: Glossary by Progress Planner - Nested Term Linking

## Overview

This roadmap delivers nested term linking for the Glossary by Progress Planner plugin. When complete, glossary terms mentioned within glossary definitions will automatically link to their entries on the glossary page, creating a connected knowledge graph. Implementation touches the Content_Filter class to process glossary content and the Blocks class to render linked terms in the glossary display.

## Phases

- [x] **Phase 1: Nested Term Linking** - Enable term auto-linking within glossary definitions and block output

## Phase Details

### Phase 1: Nested Term Linking
**Goal**: Terms mentioned in glossary definitions link to their entries on the glossary page
**Depends on**: Nothing (brownfield enhancement)
**Requirements**: NEST-01, NEST-02, NEST-03, NEST-04, NEST-05, NEST-06, NEST-07
**Success Criteria** (what must be TRUE):
  1. User viewing glossary block sees clickable links when one term references another term
  2. User viewing popover sees clickable links when definition references another term
  3. User clicking nested link navigates to glossary page with correct anchor
  4. A term's own definition never contains a self-link (e.g., "API" definition doesn't link the word "API")
  5. Nested links use same visual style as inline term triggers (dotted underline)
**Plans**: 2 plans

Plans:
- [x] 01-01-PLAN.md — Create Term_Linker utility class and CSS styling
- [x] 01-02-PLAN.md — Wire Term_Linker into Content_Filter and Blocks

## Progress

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Nested Term Linking | 2/2 | ✓ Complete | 2026-02-04 |
