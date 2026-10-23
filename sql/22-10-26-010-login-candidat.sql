-- Comptes candidats de démonstration
-- Mot de passe : candidat123
-- Hash généré avec password_hash('candidat123', PASSWORD_DEFAULT)

UPDATE candidats
SET mdp = '$2y$10$Q7aGDb63h2EIXjtJye0d5O8jAKL5tdlPLWIN2K19pp/QPBPlDKAc6'
WHERE login IN ('miora_test', 'tiana_test');
