const blockedKeys = new Set(['F12'])
const blockedCtrlShiftKeys = new Set(['I', 'J', 'C'])
const blockedCtrlKeys = new Set(['U', 'S'])

function isInspectShortcut(event) {
  const key = event.key?.toUpperCase()

  if (blockedKeys.has(event.key)) {
    return true
  }

  if (event.ctrlKey && event.shiftKey && blockedCtrlShiftKeys.has(key)) {
    return true
  }

  if (event.ctrlKey && blockedCtrlKeys.has(key)) {
    return true
  }

  return false
}

export function disableInspectShortcuts() {
  window.addEventListener(
    'contextmenu',
    (event) => {
      event.preventDefault()
      event.stopPropagation()
    },
    true
  )

  window.addEventListener(
    'keydown',
    (event) => {
      if (isInspectShortcut(event)) {
        event.preventDefault()
        event.stopPropagation()
      }
    },
    true
  )
}
