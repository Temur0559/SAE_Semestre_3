# SAE S3 2025 

## Organisation Git

- **main** : stable (livraisons validées)
- **develop** : intégration continue
- **sprint/sprint-N** : branches de sprint (1 → 4)
- **feature/** : une fonctionnalité par user story  
- **fix/** : correctif pendant un sprint  
- **hotfix/** : correctif urgent en prod

## Cycle de développement

1. Créer une branche `feature/US-XX-description` depuis `sprint/sprint-N`.
2. Développer, commit, push.
3. Ouvrir une PR → merge vers `sprint/sprint-N`.
4. Fin de sprint : merge `sprint/sprint-N` → `develop`.
5. Release : merge `develop` → `main` + tag `v0.N.0`.

## Planning des livraisons

- Sprint 1 → `v0.1.0`
- Sprint 2 → `v0.2.0`
- Sprint 3 → `v0.3.0`
- Sprint 4 → `v0.4.0`
