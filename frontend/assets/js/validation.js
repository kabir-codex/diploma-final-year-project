// ============================================================
//  validation.js — Shared client-side form validation
//  Loaded on every page via footer.php.
//  Applies to all <form> elements automatically:
//    - Required field validation (adds a friendly message)
//    - Email format validation (any input[type="email"])
//    - Mobile number validation: exactly 10 numeric digits
//      (any input whose name/id contains "phone" or "mobile")
// ============================================================

(function () {

    var EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;   //check if an email address has a valid format

    // Checks whether an input field is related to a phone/mobile number
    
    function isPhoneField(input) {
        var key = (input.name + ' ' + input.id).toLowerCase();          // Combine the input name and id, then convert to lowercase
        return key.indexOf('phone') !== -1 || key.indexOf('mobile') !== -1;   // Return true if the field name or id contains "phone" or "mobile"
    }

    function showError(input, message) {
        clearError(input);
        var msg = document.createElement('small');
        msg.className = 'field-error';
        msg.textContent = message;
        input.classList.add('input-error');
        input.insertAdjacentElement('afterend', msg);
    }

    function clearError(input) {
        input.classList.remove('input-error');
        var next = input.nextElementSibling;
        if (next && next.classList.contains('field-error')) {
            next.parentNode.removeChild(next);
        }
    }

    function validateField(input) {
        var value = input.value.trim();

        // Required field check
        if (input.hasAttribute('required') && value === '') {
            showError(input, 'This field is required.');
            return false;
        }

        // Email format check
        if (input.type === 'email' && value !== '' && !EMAIL_REGEX.test(value)) {
            showError(input, 'Please enter a valid email address.');
            return false;
        }

        // Mobile number check: exactly 10 digits
        if (isPhoneField(input) && value !== '') {  //checks the phone filed if its blank
            if (!/^[0-9]{10}$/.test(value)) {        //checks input doesnt have only numbers and have 10 digits
                showError(input, 'Mobile number must be exactly 10 digits.');     //then gives this ouput
                return false;
            }
        }

        clearError(input);
        return true;
    }

    function attachPhoneDigitFilter(input) {
        // Only allow numeric characters to be typed, capped at 10 digits
        input.addEventListener('input', function () {
            input.value = input.value.replace(/[^0-9]/g, '').slice(0, 10);
        });
    }

    function setupForm(form) {
        var fields = form.querySelectorAll('input, select, textarea');

        fields.forEach(function (input) {
            if (isPhoneField(input) && input.type !== 'hidden') {
                attachPhoneDigitFilter(input);
            }
            // Validate as the person leaves a field
            input.addEventListener('blur', function () {
                validateField(input);
            });
        });

        form.addEventListener('submit', function (e) {
            var valid = true;
            fields.forEach(function (input) {
                if (input.type === 'hidden' || input.disabled) return;
                if (!validateField(input)) valid = false;
            });
            if (!valid) {
                e.preventDefault();
                var firstError = form.querySelector('.input-error');
                if (firstError) firstError.focus();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form').forEach(setupForm);
    });

})();

