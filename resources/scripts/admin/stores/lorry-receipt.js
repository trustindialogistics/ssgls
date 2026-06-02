import http from '@/scripts/http'
import { defineStore } from 'pinia'
import { handleError } from '@/scripts/helpers/error-handling'

export const useLorryReceiptStore = (useWindow = false) => {
  const defineStoreFunc = useWindow ? window.pinia.defineStore : defineStore

  return defineStoreFunc('lorryReceipt', {
    state: () => ({
      lorryReceipts: [],
      lorryReceiptTotalCount: 0,
      current: null,
    }),

    actions: {
      fetchLorryReceipts(params) {
        return new Promise((resolve, reject) => {
          http
            .get('/api/v1/lorry-receipts', { params })
            .then((response) => {
              this.lorryReceipts = response.data.data
              this.lorryReceiptTotalCount = response.data.meta?.total || 0
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      fetchLorryReceipt(id) {
        return new Promise((resolve, reject) => {
          http
            .get(`/api/v1/lorry-receipts/${id}`)
            .then((response) => {
              this.current = response.data.data
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      createLorryReceipt(payload) {
        return new Promise((resolve, reject) => {
          http
            .post('/api/v1/lorry-receipts', payload)
            .then((response) => resolve(response))
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      updateLorryReceipt(id, payload) {
        return new Promise((resolve, reject) => {
          http
            .put(`/api/v1/lorry-receipts/${id}`, payload)
            .then((response) => resolve(response))
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      deleteLorryReceipt(id) {
        return new Promise((resolve, reject) => {
          http
            .delete(`/api/v1/lorry-receipts/${id}`)
            .then((response) => resolve(response))
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },
    },
  })()
}
