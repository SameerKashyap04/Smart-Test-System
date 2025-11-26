<?php
/**
 * Password Validation Functions
 * Enhanced security for user registration
 */

class PasswordValidator {
    private $minLength = 8;
    private $maxLength = 128;
    
    public function __construct($minLength = 8) {
        $this->minLength = $minLength;
    }
    
    /**
     * Validate password strength
     * @param string $password
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validatePassword($password) {
        $errors = [];
        
        // Check minimum length
        if (strlen($password) < $this->minLength) {
            $errors[] = "Password must be at least {$this->minLength} characters long";
        }
        
        // Check maximum length
        if (strlen($password) > $this->maxLength) {
            $errors[] = "Password must be no more than {$this->maxLength} characters long";
        }
        
        // Check for uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        
        // Check for lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        
        // Check for number
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
        
        // Check for special character
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?~`]/', $password)) {
            $errors[] = "Password must contain at least one special character (!@#$%^&*()_+-=[]{}|;':\",./<>?~`)";
        }
        
        // Check for common weak patterns
        if (preg_match('/^(.)\1+$/', $password)) {
            $errors[] = "Password cannot be all the same character";
        }
        
        if (preg_match('/^(123|abc|qwe|asd|zxc)/i', $password)) {
            $errors[] = "Password cannot start with common patterns";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Get password strength score (0-100)
     * @param string $password
     * @return int
     */
    public function getPasswordStrength($password) {
        $score = 0;
        $length = strlen($password);
        
        // Length score (0-30 points)
        if ($length >= $this->minLength) {
            $score += min(30, ($length - $this->minLength) * 2);
        }
        
        // Character variety score (0-70 points)
        if (preg_match('/[a-z]/', $password)) $score += 10;
        if (preg_match('/[A-Z]/', $password)) $score += 10;
        if (preg_match('/[0-9]/', $password)) $score += 10;
        if (preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?~`]/', $password)) $score += 10;
        
        // Complexity bonus
        if (preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?~`].*[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?~`]/', $password)) {
            $score += 10; // Multiple special characters
        }
        
        if ($length > 12) $score += 10; // Extra length bonus
        if ($length > 16) $score += 10; // Very long password bonus
        
        return min(100, $score);
    }
    
    /**
     * Get password strength description
     * @param int $score
     * @return string
     */
    public function getStrengthDescription($score) {
        if ($score < 30) return 'Very Weak';
        if ($score < 50) return 'Weak';
        if ($score < 70) return 'Fair';
        if ($score < 90) return 'Good';
        return 'Strong';
    }
    
    /**
     * Get password strength color class
     * @param int $score
     * @return string
     */
    public function getStrengthColor($score) {
        if ($score < 30) return 'danger';
        if ($score < 50) return 'warning';
        if ($score < 70) return 'info';
        if ($score < 90) return 'primary';
        return 'success';
    }
}
?>
