// Handle attack link clicks
document
  .querySelector(".attack-link")
  ?.addEventListener("click", function (event) {
    let confirmAction = confirm(
      "Warning: You're about to view a CSRF attack demonstration. This is for educational purposes only. Proceed?"
    );
    if (!confirmAction) {
      event.preventDefault();
    }
  });

// Add form validation
document.querySelectorAll("form").forEach((form) => {
  form.addEventListener("submit", function (event) {
    const amount = this.querySelector('input[name="amount"]');
    const toUser = this.querySelector('input[name="to_user"]');

    if (amount && (amount.value <= 0 || isNaN(amount.value))) {
      event.preventDefault();
      alert("Amount must be greater than 0");
      return;
    }

    if (toUser && (toUser.value <= 0 || isNaN(toUser.value))) {
      event.preventDefault();
      alert("User ID must be valid");
      return;
    }
  });
});

// Add auto-hiding messages with fade effect
document.querySelectorAll(".success, .error").forEach((message) => {
  if (!message.classList.contains("permanent")) {
    // Don't auto-hide if permanent class exists
    setTimeout(() => {
      message.style.opacity = "0";
      message.style.transition = "opacity 0.5s";
      setTimeout(() => {
        if (message.parentNode) {
          // Check if element still exists
          message.remove();
        }
      }, 500);
    }, 3000);
  }
});
