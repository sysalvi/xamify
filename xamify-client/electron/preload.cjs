const { contextBridge, ipcRenderer } = require('electron')

contextBridge.exposeInMainWorld('examAPI', {
  startExam: () => {
    console.log('[XAMIFY] preload startExam')
    ipcRenderer.send('exam:start')
  },
  exitExam: () => {
    console.log('[XAMIFY] preload exitExam')
    ipcRenderer.send('exam:exit')
  },
  getLocalIp: () => ipcRenderer.invoke('exam:get-ip'),
  ping: () => ipcRenderer.invoke('exam:ping'),
})
