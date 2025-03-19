// Import Firebase modules from version 9.22.0
import { initializeApp } from "https://www.gstatic.com/firebasejs/9.22.0/firebase-app.js";
import { getAuth, signInWithPopup, GoogleAuthProvider } from "https://www.gstatic.com/firebasejs/9.22.0/firebase-auth.js";

console.log("Corrected main.js loaded using Firebase 9.22.0");

// Firebase configuration
const firebaseConfig = {
  apiKey: "AIzaSyA4Rm97N9BLC3UAZCk9VdxjaKebeFOLzsY",
  authDomain: "signin-e16c8.firebaseapp.com",
  projectId: "signin-e16c8",
  storageBucket: "signin-e16c8.firebasestorage.app",
  messagingSenderId: "635572360991",
  appId: "1:635572360991:web:212083deb5c62e0f1c822a"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const auth = getAuth(app);
const provider = new GoogleAuthProvider();

// Force account selection prompt
provider.setCustomParameters({
    prompt: 'select_account'
});

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
  console.log("main.js: DOM Content Loaded Event");
  
  // Find Google button
  const googleBtn = document.getElementById('google-login-btn');
  if (googleBtn) {
    console.log("Google button found successfully");
    
    // Add click event
    googleBtn.addEventListener('click', function(e) {
      e.preventDefault();
      console.log("Google login button clicked");
      
      signInWithPopup(auth, provider)
        .then((result) => {
          console.log("Google sign in successful", result.user);
          
          // Send to backend
          fetch('process_google_login.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              email: result.user.email,
              name: result.user.displayName,
              uid: result.user.uid,
              photoURL: result.user.photoURL
            })
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              window.location.href = data.redirect;
            } else {
              alert('Login failed: ' + data.message);
            }
          })
          .catch(error => {
            console.error("Backend error:", error);
            alert('Backend error: ' + error);
          });
        })
        .catch((error) => {
          console.error("Sign in error:", error);
          alert('Sign in failed: ' + error.message);
        });
    });
  } else {
    console.error("Google login button not found in the DOM");
  }
});

// Log that script has fully executed
console.log("main.js: Script execution completed");