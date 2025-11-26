document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    // Tab switching functionality
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const tabId = button.getAttribute('data-tab');
            
            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.style.display = 'none');
            
            // Add active class to clicked button and show corresponding content
            button.classList.add('active');
            document.getElementById(tabId).style.display = 'block';
        });
    });

    // Password validation
    const passwordInput = document.getElementById('signup-password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            validatePasswordWithFeedback(password);
        });
    }

    // Form validation
    const signupForm = document.querySelector('#signup form');
    if (signupForm) {
        signupForm.addEventListener('submit', function(e) {
            const password = document.getElementById('signup-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            
            // Validate password strength
            const passwordValidation = validatePassword(password);
            if (!passwordValidation.valid) {
                e.preventDefault();
                alert('Password requirements not met:\n' + passwordValidation.errors.join('\n'));
                return;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    }

    // Password visibility toggle
    document.querySelectorAll('.toggle-password').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetSelector = btn.getAttribute('data-target');
            if (!targetSelector) return;
            const input = document.querySelector(targetSelector);
            if (!input) return;
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                if (icon) { icon.classList.remove('fa-eye'); icon.classList.add('fa-eye-slash'); }
            } else {
                input.type = 'password';
                if (icon) { icon.classList.remove('fa-eye-slash'); icon.classList.add('fa-eye'); }
            }
        });
    });
});

// Password validation functions
function validatePassword(password) {
    const errors = [];
    
    // Check minimum length
    if (password.length < 8) {
        errors.push("Password must be at least 8 characters long");
    }
    
    // Check uppercase letter
    if (!/[A-Z]/.test(password)) {
        errors.push("Password must contain at least one uppercase letter");
    }
    
    // Check lowercase letter
    if (!/[a-z]/.test(password)) {
        errors.push("Password must contain at least one lowercase letter");
    }
    
    // Check number
    if (!/[0-9]/.test(password)) {
        errors.push("Password must contain at least one number");
    }
    
    // Check special character
    if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`]/.test(password)) {
        errors.push("Password must contain at least one special character");
    }
    
    // Check for common weak patterns
    if (/^(.)\1+$/.test(password)) {
        errors.push("Password cannot be all the same character");
    }
    
    if (/^(123|abc|qwe|asd|zxc)/i.test(password)) {
        errors.push("Password cannot start with common patterns");
    }
    
    return {
        valid: errors.length === 0,
        errors: errors
    };
}

function updateRequirement(elementId, isValid) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    const icon = element.querySelector('i');
    
    if (isValid) {
        element.classList.remove('text-danger');
        element.classList.add('text-success');
        icon.classList.remove('fa-times');
        icon.classList.add('fa-check');
    } else {
        element.classList.remove('text-success');
        element.classList.add('text-danger');
        icon.classList.remove('fa-check');
        icon.classList.add('fa-times');
    }
}

function updatePasswordStrength(password) {
    const strengthBar = document.getElementById('strength-bar');
    const strengthText = document.getElementById('strength-text');
    
    if (!strengthBar || !strengthText) return;
    
    let score = 0;
    let strength = 'Very Weak';
    let color = 'danger';
    
    if (password.length === 0) {
        strength = 'Enter a password';
        color = 'secondary';
    } else {
        // Length score
        if (password.length >= 8) score += 20;
        if (password.length >= 12) score += 10;
        if (password.length >= 16) score += 10;
        
        // Character variety score
        if (/[a-z]/.test(password)) score += 10;
        if (/[A-Z]/.test(password)) score += 10;
        if (/[0-9]/.test(password)) score += 10;
        if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`]/.test(password)) score += 10;
        
        // Complexity bonus
        if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`].*[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`]/.test(password)) {
            score += 10; // Multiple special characters
        }
        
        // Determine strength level
        if (score < 30) {
            strength = 'Very Weak';
            color = 'danger';
        } else if (score < 50) {
            strength = 'Weak';
            color = 'warning';
        } else if (score < 70) {
            strength = 'Fair';
            color = 'info';
        } else if (score < 90) {
            strength = 'Good';
            color = 'primary';
        } else {
            strength = 'Strong';
            color = 'success';
        }
    }
    
    // Update progress bar
    strengthBar.style.width = score + '%';
    strengthBar.className = `progress-bar bg-${color}`;
    
    // Update strength text
    strengthText.textContent = strength;
    strengthText.className = `text-${color}`;
}

// Enhanced password validation with visual feedback
function validatePasswordWithFeedback(password) {
    // Check length
    const lengthValid = password.length >= 8;
    updateRequirement('req-length', lengthValid);
    
    // Check uppercase
    const uppercaseValid = /[A-Z]/.test(password);
    updateRequirement('req-uppercase', uppercaseValid);
    
    // Check lowercase
    const lowercaseValid = /[a-z]/.test(password);
    updateRequirement('req-lowercase', lowercaseValid);
    
    // Check number
    const numberValid = /[0-9]/.test(password);
    updateRequirement('req-number', numberValid);
    
    // Check special character
    const specialValid = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`]/.test(password);
    updateRequirement('req-special', specialValid);
    
    // Calculate strength
    updatePasswordStrength(password);
    
    // Return validation result
    return validatePassword(password);
} 