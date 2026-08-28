---
paths:
  - 'tests/Feature/**'
---

# Feature

## Rebuild Vite après ajout de page Inertia
Le starter kit résout chaque page via @vite("resources/js/pages/{component}.vue"). Après avoir ajouté une page .vue, lancer `npm run build` sinon les feature tests qui rendent la page échouent en 500 (ViteException: Unable to locate file in Vite manifest). Pour les tests de rôle, penser à forgetCachedPermissions() en beforeEach (cache spatie array persistant entre tests).
