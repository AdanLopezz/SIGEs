import './bootstrap';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap';

document.addEventListener('DOMContentLoaded', () => {

    const container = document.getElementById('authContainer');

    const signUpBtn = document.getElementById('signUpBtn');
    const signInBtn = document.getElementById('signInBtn');

    const mobileSignUp = document.getElementById('mobileSignUp');
    const mobileSignIn = document.getElementById('mobileSignIn');

    if (signUpBtn) {
        signUpBtn.addEventListener('click', () => {
            container.classList.add('register-active');
        });
    }

    if (signInBtn) {
        signInBtn.addEventListener('click', () => {
            container.classList.remove('register-active');
        });
    }

    if (mobileSignUp) {
        mobileSignUp.addEventListener('click', () => {
            container.classList.add('register-active');
        });
    }

    if (mobileSignIn) {
        mobileSignIn.addEventListener('click', () => {
            container.classList.remove('register-active');
        });
    }

    document
        .querySelectorAll('.password-toggle')
        .forEach(button => {

            button.addEventListener('click', () => {

                const input = document.getElementById(
                    button.dataset.passwordTarget
                );

                if (!input) {
                    return;
                }

                input.type =
                    input.type === 'password'
                        ? 'text'
                        : 'password';
            });

        });

});
