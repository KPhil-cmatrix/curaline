async function fetchNotifications() {
  try {
    const response = await fetch("../2-backend/get_notifications.php", {
      cache: "no-store"
    });

    const data = await response.json();

    console.log("Notifications response:", data);

    if (!Array.isArray(data) || data.length === 0) return;

    showNotificationPopup(data[0]);

  } catch (err) {
    console.error("Notification error:", err);
  }
}

function showNotificationPopup(notification) {
  const oldBox = document.getElementById("curaline-notification-popup");
  if (oldBox) oldBox.remove();

  const box = document.createElement("div");
  box.id = "curaline-notification-popup";
  box.style.position = "fixed";
  box.style.top = "24px";
  box.style.right = "24px";
  box.style.zIndex = "99999";
  box.style.background = "white";
  box.style.borderLeft = "5px solid #3EDCDE";
  box.style.boxShadow = "0 10px 25px rgba(0,0,0,0.2)";
  box.style.borderRadius = "12px";
  box.style.padding = "16px";
  box.style.maxWidth = "360px";
  box.style.fontFamily = "Arial, sans-serif";

  box.innerHTML = `
    <p style="font-weight:700;color:#2F5395;margin:0 0 6px;">Notification</p>
    <p style="font-size:14px;color:#374151;margin:0 0 12px;">${notification.message}</p>
    <button id="closeNotification" style="font-size:12px;color:#2F5395;text-decoration:underline;background:none;border:none;cursor:pointer;padding:0;">
      Dismiss
    </button>
  `;

  document.body.appendChild(box);

  document.getElementById("closeNotification").addEventListener("click", () => {
    box.remove();
  });
}

document.addEventListener("DOMContentLoaded", () => {
  fetchNotifications();
  setInterval(fetchNotifications, 5000);
});