// src/stores/toastStore.js
import { reactive } from 'vue';

export const toastStore = reactive({
  toasts: [],
  notifications: [] // <-- New array to track all notifications
});

// Add a toast and a notification
export const addToast = (toast) => {
  const id = Date.now();

  // Push toast for Toast.vue
  toastStore.toasts.push({ ...toast, id });

  // Also push to notifications panel
  toastStore.notifications.unshift({
    id,
    title: toast.title,
    message: toast.message,
    read: false,
    time: new Date()
  });

  // Auto-remove toast after 4 seconds
  setTimeout(() => {
    const index = toastStore.toasts.findIndex(t => t.id === id);
    if (index !== -1) toastStore.toasts.splice(index, 1);
  }, 4000);
};

// Mark notification as read
export const markAsRead = (id) => {
  const notif = toastStore.notifications.find(n => n.id === id);
  if (notif) notif.read = true;
};