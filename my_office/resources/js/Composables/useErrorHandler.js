import { useToast } from '@/Composables/useToast';

export function useErrorHandler() {
    const toast = useToast();

    const errorMappings = {
        401: 'Session expirée. Veuillez vous reconnecter.',
        403: "Vous n'avez pas l'autorisation d'effectuer cette action.",
        404: 'La ressource demandée est introuvable.',
        422: 'Certaines informations sont incorrectes ou manquantes.',
        500: 'Une erreur interne au serveur est survenue. Veuillez réessayer plus tard.',
    };

    const handleApiError = (error) => {
        console.error('API Error:', error);

        let errorMessage = 'Une erreur inattendue est survenue.';

        if (error.response) {
            const status = error.response.status;

            if (errorMappings[status]) {
                errorMessage = errorMappings[status];
            }

            // Specific validation error messages from Laravel
            if (status === 422 && error.response.data.message) {
                errorMessage = error.response.data.message;
            } else if (error.response.data && error.response.data.message) {
                // If backend provides a specific message, use it instead of mapping
                errorMessage = error.response.data.message;
            }
        } else if (error.message) {
            errorMessage = error.message;
        }

        toast.error(errorMessage);

        return {
            message: errorMessage,
            originalError: error,
        };
    };

    return {
        handleApiError,
    };
}
