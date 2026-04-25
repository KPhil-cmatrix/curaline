function toggleNotifications() {
  const box = document.getElementById('notif-box');
  if (box) {
    box.classList.toggle('hidden');
  }
}

async function fetchNotifications() {
  try {
    const response = await fetch('../2-backend/get_notifications.php');
    const data = await response.json();

    const container = document.getElementById('notifications-container');
    if (!container) return;

    let html = '';

    if (!Array.isArray(data) || data.length === 0) {
      html = "<p class='text-gray-400 text-sm'>No notifications yet.</p>";
    } else {
      data.forEach(notif => {
        html += `
          <div class="bg-[#EEF3FA] p-2 rounded mb-2 text-sm">
            ${notif.message}
            <div class="text-xs text-gray-400">
              ${new Date(notif.created_at).toLocaleString()}
            </div>
          </div>
        `;
      });
    }

    container.innerHTML = html;
  } catch (err) {
    console.error('Notification error:', err);
  }
}

document.addEventListener('DOMContentLoaded', function () {
  fetchNotifications();
  setInterval(fetchNotifications, 5000);
});