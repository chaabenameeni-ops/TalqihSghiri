document.addEventListener("DOMContentLoaded", function () {

const loginForm = document.getElementById("login-form");
const toggleIcon = document.querySelector(".toggle-password");

/* show / hide password */
document.querySelectorAll('.toggle-password').forEach(icon => {
  icon.addEventListener('click', () => {
    const input = document.getElementById(icon.getAttribute('data-target'));

    if (input.type === "password") {
      input.type = "text";
      icon.classList.remove("fa-eye");
      icon.classList.add("fa-eye-slash");
    } else {
      input.type = "password";
      icon.classList.remove("fa-eye-slash");
      icon.classList.add("fa-eye");
    }
  });
});

});
/* login submit */

loginForm.addEventListener("submit", function (e) {

e.preventDefault();

const email = document.getElementById("email").value.trim();
const role = document.getElementById("role").value;
const password = document.getElementById("password").value;

const submitBtn = loginForm.querySelector(".btn-login");

/* validation */

if (!email || !role || !password) {

alert("Veuillez remplir tous les champs !");
return;

}

     const emailRegex = /^[^\s@]+@(gmail\.com|yahoo\.(com|fr)|outlook\.com)$/;
        if (!emailRegex.test(email)) {
    alert("L'adresse email doit être un compte @gmail.com, @yahoo ou @outlook !");
    return;
}


/* loading button */

submitBtn.innerText = "Connexion...";
submitBtn.disabled = true;


/* save session */

sessionStorage.setItem("role", role);
sessionStorage.setItem("userEmail", email);




});



