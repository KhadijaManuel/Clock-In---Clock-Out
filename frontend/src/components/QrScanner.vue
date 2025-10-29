<template>
  <div class="header">
    <h2>Clock In</h2>
    <button class="circle-btn">
      <img src="@/assets/arrow-icon.png" alt="Arrow Icon" class="arrow-icon" />
    </button>
  </div>

  <div class="scanner-container">
    <button class="clock-in">Clock In</button>

    <h3 class="scanner-title">
      <img :src="headerIcon" alt="Clock Icon" class="title-icon" />
      QR Code Scan
    </h3>

    <p class="instruction">Align the QR code within the frame to clock in.</p>

    <div id="reader" class="scanner-box"></div>

    <transition name="fade">
      <div v-if="message" class="error-page">
        <div
          class="error-card"
          :class="{ success: statusClass === 'success', error: statusClass === 'error' }"
        >
          <img
            v-if="statusClass === 'error'"
            :src="errorIcon"
            alt="Error Icon"
            class="error-icon"
          />
          <img
            v-else
            :src="successIcon"
            alt="Success Icon"
            class="error-icon"
          />
          <p>{{ message }}</p>
        </div>
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
import successIcon from "@/assets/icons8-checkmark-48.png";
import headerIcon from "@/assets/icons8-qr-code-24.png";

export default {
  name: "QrClockScanner",
  data() {
    return {
      message: "",
      statusClass: "",
      errorIcon,
      successIcon,
      headerIcon,
    };
  },
  mounted() {
    const scanner = new Html5QrcodeScanner("reader", {
      fps: 10,
      qrbox: 250,
      rememberLastUsedCamera: true,
      supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA],
    });

    // ✅ Render and handle QR scans
    scanner.render(
      async (decodedText) => {
        try {
          if (!navigator.geolocation) {
            this.showMessage("Geolocation not supported by browser!", "error");
            return;
          }

          navigator.geolocation.getCurrentPosition(
            async (pos) => {
              const { latitude, longitude } = pos.coords;

              try {
                const response = await axios.post("http://localhost:3000/scan", {
                  qrData: decodedText,
                  latitude,
                  longitude,
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
            () => {
              this.showMessage("Location permission denied or unavailable!", "error");
            }
          );
        } catch (err) {
          this.showMessage("Unexpected error occurred!", "error");
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
/* ✅ Base container */
.scanner-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100vh;
  background: #e6f7f2;
  color: #000;
  position: relative;
}

.header {
  background-color: #2eb28a;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 14px;
  gap: 500px;
  height: 60px;
  padding: 0 15px;
  position: relative;
  margin-top: 3px;
}
.circle-btn {
  position: absolute;
  right: 15px;
  background: none;
  border: none;
  padding: 0;
  cursor: pointer;
}
.arrow-icon {
  width: 24px;
  height: 24px;
}
.instruction {
  margin-bottom: 40px;
  margin-top: 30px;
}

/* ✅ Top-right Clock In button */
.clock-in {
  position: absolute;
  top: 40px;
  right: 100px;
  background: #daf1ec;
  color: black;
  border: 2px solid #2eb28a;
  padding: 8px 40px;
  border-radius: 4px;
  font-weight: 600;
  cursor: pointer;
}

/* ✅ Title with icon */
.scanner-title {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  font-size: 18px;
  margin-bottom: 10px;
  border: 2px solid #2eb28a;
  padding: 8px 90px;
  border-radius: 4px;
  background: #daf1ec;
}
.title-icon {
  width: 26px;
  height: 26px;
  object-fit: contain;
}
.scanner-box {
  width: 320px;
  border-radius: 10px;
  overflow: hidden;
}

/* ✅ Message card */
.error-page {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-top: 25px;
  width: 100%;
}
.error-card {
  border-radius: 8px;
  padding: 20px 30px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 350px;
  max-width: 90%;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
  font-size: 18px;
  font-weight: 500;
  text-align: center;
  border: 1px solid black;
}
.error,
.success {
  background-color: #2eb28a;
  color: #000;
}
.error-icon {
  width: 40px;
  height: 40px;
  margin-right: 15px;
}

/* ✅ Fade Animation */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s;
}
.fade-enter,
.fade-leave-to {
  opacity: 0;
}

/* ✅ Tablet General */
@media (max-width: 1024px) {
  .header {
    gap: 200px;
    font-size: 17px;
  }
  .clock-in {
    right: 40px;
    padding: 8px 25px;
  }
  .scanner-title {
    padding: 8px 60px;
  }
  .scanner-box {
    width: 300px;
  }
}

/* ✅ Mobile General */
@media (max-width: 800px) {
  .header {
    flex-direction: row;
    justify-content: center;
    gap: 0;
    height: 50px;
    font-size: 16px;
  }
  .circle-btn {
    right: 10px;
  }
  .scanner-container {
    padding: 20px;
    justify-content: flex-start;
  }
  .clock-in {
    position: static;
    margin-bottom: 20px;
    width: 100%;
  }
  .scanner-title {
    width: 100%;
    padding: 10px 0;
    font-size: 16px;
  }
  .scanner-box {
    width: 100%;
    max-width: 280px;
  }
  .error-card {
    width: 90%;
    font-size: 16px;
  }
}

/* ✅ iPad Specific */
@media (max-width: 820px) and (min-width: 700px) {
  .header {
    gap: 250px;
    font-size: 17px;
    height: 55px;
  }
  .circle-btn {
    right: 20px;
  }
  .scanner-container {
    padding: 40px 20px;
  }
  .clock-in {
    top: 30px;
    right: 60px;
    padding: 10px 35px;
  }
  .scanner-title {
    padding: 10px 70px;
    font-size: 17px;
  }
  .scanner-box {
    width: 320px;
  }
  .error-card {
    width: 320px;
    font-size: 17px;
  }
}

/* ✅ Nest Hub Specific */
@media (max-width: 1024px) and (min-width: 900px) {
  .header {
    gap: 300px;
    font-size: 18px;
  }
  .scanner-container {
    padding-top: 60px;
  }
  .clock-in {
    top: 35px;
    right: 70px;
    padding: 10px 40px;
  }
  .scanner-title {
    padding: 10px 80px;
    font-size: 18px;
  }
  .scanner-box {
    width: 340px;
  }
  .error-card {
    width: 340px;
    font-size: 18px;
  }
}
</style>
