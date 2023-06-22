package main

import (
	"encoding/json"
	"flag"
	"io/ioutil"
	"log"
	"math/rand"
	"net/http"
	"os"
	"sync"
	"time"
)

type analyticsHandler struct{}

type postData struct {
	AuthorId  uint   `json:"authorId"`
	PostId    uint   `json:"postId"`
	PostTitle string `json:"postTitle"`
	Action    string `json:"action"`
}

// все входящие запросы сюда и разбираем
func (h *analyticsHandler) ServeHTTP(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("content-type", "application/json")
	logInfo := log.New(os.Stdout, "INFO\t", log.Ldate|log.Ltime)
	switch {
	case r.Method == http.MethodPost:
		var post postData
		var wg sync.WaitGroup
		const min, max = 3, 15

		reqBody, err := ioutil.ReadAll(r.Body)
		if err != nil {
			log.Printf("Ошибка чтения body: %v\n", err)
			return
		}
		err = json.Unmarshal(reqBody, &post)
		if err != nil {
			internalServerError(w, r)
			log.Printf("Ошибка unmarshal: %v\n", err)
			return
		}

		jsonBytes, err := json.Marshal(post)
		if err != nil {
			internalServerError(w, r)
			log.Printf("Ошибка marshal: %v\n", err)
		}

		timeout := time.Duration(rand.Intn(max-min)+min) * time.Second

		servStatus := 200

		//Сделал определение статуса ошибки через канал (в академических целях)
		status := make(chan bool)

		//Устанавливаем счетчик wg в 2, т.к. у нас 2 горутины
		wg.Add(2)

		go func() {
			//Уменьшаем счетчик wg после завершения выполнения горутины
			defer wg.Done()
			time.Sleep(timeout)
		}()

		go func() {
			//Аналогично для второй горутины
			defer wg.Done()
			status <- (post.PostId%3 == 0)
		}()

		if <-status {
			internalServerError(w, r)
			servStatus = 500
			close(status)
		} else {
			ok(w, r)
		}

		//ждем завершения обоих горутин, wg должен обнулиться
		wg.Wait()

		logInfo.Printf("Post: %+v, Status: %+v, Time: %+v,", string(jsonBytes), servStatus, timeout)

		return

	default:
		notFound(w, r)
		return
	}
}

func ok(w http.ResponseWriter, r *http.Request) {
	w.WriteHeader(http.StatusOK)
	w.Write([]byte("Ok"))
}

func internalServerError(w http.ResponseWriter, r *http.Request) {
	w.WriteHeader(http.StatusInternalServerError)
	w.Write([]byte("Internal server error"))
}

func notFound(w http.ResponseWriter, r *http.Request) {
	w.WriteHeader(http.StatusNotFound)
	w.Write([]byte("Not found"))
}

func main() {

	addr := flag.String("addr", ":8080", "Сетевой адрес веб-сервера")
	flag.Parse()
	logError := log.New(os.Stderr, "ERROR\t", log.Ldate|log.Ltime|log.Lshortfile)

	mux := http.NewServeMux()
	mux.Handle("/analytics", &analyticsHandler{})

	srv := &http.Server{
		Addr:     *addr,
		ErrorLog: logError,
		Handler:  mux,
	}

	log.Printf("Старт API сбора аналитики постов v1.0 на %s", *addr)
	err := srv.ListenAndServe()
	log.Fatal(err)
}
