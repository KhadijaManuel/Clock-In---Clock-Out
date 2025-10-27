<template>
  <div class="scanner-container">
    <h2>Employee QR Clock System</h2>
    <p class="instruction">Please scan your QR code to clock in or out</p>

    <div id="reader" class="scanner-box"></div>

    <transition name="fade">
      <div v-if="message" :class="['message', statusClass]">
        <img
          v-if="statusClass === 'error'"
          :src="errorIcon"
          alt="error icon"
          class="icon"
        />
        {{ message }}
      </div>
    </transition>
  </div>
</template>

<script>
import { Html5QrcodeScanner, Html5QrcodeScanType } from "html5-qrcode";
import axios from "axios";
import successSound from "@/assets/success.mp3";
import errorSound from "@/assets/error.mp3";
import errorIcon from "@/assets/icons8-error-48.png";

export default {
  name: "QrClockScanner",
  data() {
    return {
      message: "",
      statusClass: "",
      errorIcon,
    };
  },
  mounted() {
    const scanner = new Html5QrcodeScanner("reader", {
      fps: 10,
      qrbox: 250,
      rememberLastUsedCamera: true,
      supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA],
    });

    scanner.render(
      async (decodedText) => {
        try {
          const response = await axios.post("http://localhost:3000/scan", {
            qrData: decodedText,
          });

          if (response.data.success) {
            this.showMessage(`✅ ${response.data.message}`, "success");
          } else {
            this.showMessage(`${response.data.message}`, "error");
          }
        } catch (err) {
          this.showMessage("API Error: Could not connect to the server!", "error");
        }
      },
      (error) => console.warn(`Scan error: ${error}`)
    );
  },
  methods: {
    showMessage(text, type) {
      this.message = text;
      this.statusClass = type;

      const sound = new Audio(type === "success" ? successSound : errorSound);
      sound.play();

      setTimeout(() => {
        this.message = "";
        this.statusClass = "";
      }, 3000);
    },
  },
};
</script>

<style scoped>
.scanner-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100vh;
  background: #daf1ec;
  color: #000;
}

h2 {
  margin-bottom: 10px;
}

.instruction {
  background: #a5d6a7;
  color: #1b1b1b;
  padding: 8px 14px;
  border-radius: 30px;
  margin-bottom: 15px;
  font-weight: 500;
}

.scanner-box {
  width: 320px;
  border-radius: 10px;
  overflow: hidden;
}

.message {
  margin-top: 20px;
  padding: 15px 20px;
  border-radius: 8px;
  font-size: 1rem;
  text-align: center;
  min-width: 280px;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.icon {
  width: 50px;
  height: 50px;
}

.success {
  background: #c8e6c9;
  color: #1b5e20;
  border: 2px solid #81c784;
}

.error {
  background: #ffcdd2;
  color: #b71c1c;
  border: 2px solid #ef9a9a;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s;
}
.fade-enter,
.fade-leave-to {
  opacity: 0;
}
</style>