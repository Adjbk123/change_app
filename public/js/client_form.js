document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('clientForm');
    const steps = document.querySelectorAll('.form-step');
    const progressSteps = document.querySelectorAll('.progress-step');
    const typeClientSelect = document.getElementById('typeClient');
    const entrepriseFields = document.getElementById('entrepriseFields');
    let currentStep = 0;

    function showStep(stepIndex) {
        steps.forEach((step, index) => {
            if (index === stepIndex) {
                step.classList.add('active');
                step.style.display = 'block';
            } else {
                step.classList.remove('active');
                step.style.display = 'none';
            }
        });
        updateProgress(stepIndex);
    }

    function updateProgress(stepIndex) {
        progressSteps.forEach((pStep, index) => {
            if (index === stepIndex) {
                pStep.classList.add('active');
            } else {
                pStep.classList.remove('active');
            }
        });
    }

    // Gérer l'affichage et le caractère requis des champs entreprise
    function toggleEntrepriseFields() {
        const selectedOption = typeClientSelect.options[typeClientSelect.selectedIndex];
        // Correction de l'erreur: Assurez-vous que selectedOption et selectedOption.dataset existent
        const typeLibelle = selectedOption && selectedOption.dataset && selectedOption.dataset.libelle
            ? selectedOption.dataset.libelle
            : '';

        const isEntreprise = typeLibelle.toLowerCase().includes('entreprise');

        if (isEntreprise) {
            entrepriseFields.style.display = 'block';
            // Ajoutez l'attribut 'required' aux champs spécifiques à l'entreprise
            entrepriseFields.querySelectorAll('[data-required-if-entreprise="true"]').forEach(input => {
                input.setAttribute('required', 'required');
            });
        } else {
            entrepriseFields.style.display = 'none';
            // Retirez l'attribut 'required' et videz les champs
            entrepriseFields.querySelectorAll('[data-required-if-entreprise="true"]').forEach(input => {
                input.removeAttribute('required');
                input.value = '';
                input.classList.remove('is-invalid'); // Nettoyer la classe de validation
            });
        }
    }

    // Écouteur d'événement sur le changement de sélection du type de client
    typeClientSelect.addEventListener('change', toggleEntrepriseFields);

    // Initialisation
    showStep(currentStep);
    toggleEntrepriseFields(); // Appeler au chargement pour le cas où une valeur par défaut serait sélectionnée

    // Logique de navigation 'Suivant'
    document.querySelectorAll('.next-step').forEach(button => {
        button.addEventListener('click', () => {
            const currentActiveStep = document.querySelector('.form-step.active');
            // Sélectionne tous les inputs qui ont l'attribut 'required' ET qui sont actuellement visibles
            // Exclut les éléments cachés par 'display: none' ou 'type="hidden"'
            const requiredInputs = currentActiveStep.querySelectorAll('[required]:not([style*="display: none"]):not([type="hidden"])');
            let allFieldsValid = true;

            requiredInputs.forEach(input => {
                // Pour les selects, vérifiez si une option a été sélectionnée (valeur non vide)
                if (input.tagName === 'SELECT') {
                    if (!input.value) {
                        input.classList.add('is-invalid');
                        allFieldsValid = false;
                    } else {
                        input.classList.remove('is-invalid');
                    }
                }
                // Pour les autres types d'inputs (text, email, number, file, date)
                else if (!input.value) {
                    input.classList.add('is-invalid');
                    allFieldsValid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            if (allFieldsValid) {
                currentStep++;
                showStep(currentStep);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Champs manquants',
                    text: 'Veuillez remplir tous les champs obligatoires.',
                    confirmButtonText: 'OK'
                });
            }
        });
    });

    // Logique de navigation 'Précédent'
    document.querySelectorAll('.prev-step').forEach(button => {
        button.addEventListener('click', () => {
            currentStep--;
            showStep(currentStep);
        });
    });
});
