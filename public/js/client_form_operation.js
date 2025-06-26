document.addEventListener('DOMContentLoaded', function() {
    // ====================================================================
    // SECTION 1: SÉLECTION DES ÉLÉMENTS DU DOM
    // ====================================================================
    const clientModalEl = document.getElementById('clientModal');
    if (!clientModalEl) {
        console.warn("L'élément avec l'ID 'clientModal' n'a pas été trouvé. Le script ne s'exécutera pas.");
        return;
    }

    const clientModal = new bootstrap.Modal(clientModalEl);
    const modalClientForm = document.getElementById('modalClientForm');
    if (!modalClientForm) {
        console.error("L'élément avec l'ID 'modalClientForm' n'a pas été trouvé. La création de client ne fonctionnera pas.");
        return;
    }

    const mainClientSelect = document.getElementById('client');
    if (!mainClientSelect) {
        console.warn("L'élément avec l'ID 'client' (le select principal des clients) n'a pas été trouvé. L'ajout dynamique du nouveau client peut échouer.");
    }

    const modalSteps = modalClientForm.querySelectorAll('.form-step');
    const modalProgressSteps = clientModalEl.querySelectorAll('.progress-step');
    const modalTypeClientSelect = modalClientForm.querySelector('#modal-typeClient');
    const modalEntrepriseFields = modalClientForm.querySelector('#modal-entrepriseFields');
    let modalCurrentStep = 0;

    // ====================================================================
    // SECTION 2: LOGIQUE POUR LA CRÉATION DE CLIENT DANS LA MODALE (AJAX)
    // ====================================================================
    modalClientForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(modalClientForm);

        Swal.fire({
            title: 'Création en cours...',
            text: 'Veuillez patienter.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch("/path/to/your/endpoint", { // Remplacez par le chemin correct de votre endpoint
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(errorData => {
                        throw new Error(errorData.message || `Erreur serveur: ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Swal.fire('Succès!', data.message, 'success');
                    const newClient = data.client;
                    if (mainClientSelect) {
                        const option = document.createElement('option');
                        option.value = newClient.id;
                        option.textContent = newClient.nomComplet;
                        option.selected = true;
                        mainClientSelect.appendChild(option);
                        mainClientSelect.dispatchEvent(new Event('change'));
                    } else {
                        console.warn("Impossible d'ajouter le nouveau client au select principal car l'élément 'client' n'a pas été trouvé.");
                    }
                    clientModal.hide();
                } else {
                    Swal.fire('Erreur', data.message || 'Un problème est survenu lors de la validation.', 'error');
                }
            })
            .catch(error => {
                console.error('Erreur Fetch:', error);
                Swal.fire('Erreur de communication', error.message || 'Impossible de contacter le serveur.', 'error');
            });
    });

    clientModalEl.addEventListener('hidden.bs.modal', function () {
        modalClientForm.reset();
        showModalStep(0);
        toggleModalEntrepriseFields();
        modalClientForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    });

    // ====================================================================
    // SECTION 3: LOGIQUE POUR LE FORMULAIRE MULTI-ÉTAPES DANS LA MODALE
    // ====================================================================
    function showModalStep(stepIndex) {
        if (stepIndex < 0 || stepIndex >= modalSteps.length) {
            console.error("Index d'étape invalide:", stepIndex);
            return;
        }

        modalSteps.forEach((step, index) => {
            step.classList.toggle('active', index === stepIndex);
        });

        modalProgressSteps.forEach((pStep, index) => {
            pStep.classList.toggle('active', index <= stepIndex);
        });

        modalCurrentStep = stepIndex;
    }

    function toggleModalEntrepriseFields() {
        if (!modalTypeClientSelect) {
            console.warn("L'élément '#modal-typeClient' n'a pas été trouvé. Les champs d'entreprise ne pourront pas être masqués/affichés dynamiquement.");
            return;
        }

        const selectedOption = modalTypeClientSelect.options[modalTypeClientSelect.selectedIndex];
        const isEntreprise = selectedOption?.dataset?.libelle?.toLowerCase().includes('entreprise');

        if (modalEntrepriseFields) {
            modalEntrepriseFields.style.display = isEntreprise ? 'block' : 'none';
            modalEntrepriseFields.querySelectorAll('[data-required-if-entreprise="true"]').forEach(input => {
                if (isEntreprise) {
                    input.setAttribute('required', 'required');
                } else {
                    input.removeAttribute('required');
                    input.classList.remove('is-invalid');
                }
            });
        } else {
            console.warn("L'élément '#modal-entrepriseFields' n'a pas été trouvé. La logique des champs entreprise ne fonctionnera pas.");
        }
    }

    if (modalTypeClientSelect) {
        modalTypeClientSelect.addEventListener('change', toggleModalEntrepriseFields);
    }

    modalClientForm.querySelectorAll('.next-step').forEach(button => {
        button.addEventListener('click', () => {
            if (validateCurrentStep()) {
                showModalStep(modalCurrentStep + 1);
            } else {
                Swal.fire('Champs manquants', 'Veuillez remplir tous les champs obligatoires.', 'warning');
            }
        });
    });

    modalClientForm.querySelectorAll('.prev-step').forEach(button => {
        button.addEventListener('click', () => {
            showModalStep(modalCurrentStep - 1);
        });
    });

    function validateCurrentStep() {
        const currentActiveStep = modalSteps[modalCurrentStep];
        let allFieldsValid = true;

        const requiredInputs = currentActiveStep.querySelectorAll('[required]:not([style*="display: none"]):not([type="hidden"])');
        requiredInputs.forEach(input => {
            input.classList.remove('is-invalid');
            if (!input.checkValidity()) {
                allFieldsValid = false;
                input.classList.add('is-invalid');
            }
            if (input.type === 'select-one' && input.value === '') {
                allFieldsValid = false;
                input.classList.add('is-invalid');
            }
            if (input.type === 'file' && input.files.length === 0) {
                allFieldsValid = false;
                input.classList.add('is-invalid');
            }
        });

        return allFieldsValid;
    }

    // Initialisation au chargement
    if (modalSteps.length > 0) {
        showModalStep(0);
    } else {
        console.warn("Aucune étape de formulaire (.form-step) trouvée dans la modale.");
    }

    toggleModalEntrepriseFields();
});
