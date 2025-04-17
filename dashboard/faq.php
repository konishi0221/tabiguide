<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ 管理</title>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        input, textarea, button {
            width: 100%;
            margin-bottom: 10px;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        button {
            background-color: #007bff;
            color: white;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .faq-list {
            margin-top: 20px;
        }
        .faq-item {
            background: #f1f1f1;
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 4px;
        }
        .faq-item button {
            background-color: red;
            padding: 5px 10px;
            border: none;
            color: white;
            cursor: pointer;
        }
    </style>
</head>
<body>

<div id="faq-form" class="container">
    <h2>FAQ 管理</h2>
    <form @submit.prevent="submitForm">
        <label for="question">質問</label>
        <input type="text" id="question" v-model="question" required placeholder="質問を入力">

        <label for="answer">回答</label>
        <textarea id="answer" v-model="answer" required placeholder="回答を入力" rows="5"></textarea>

        <button type="submit">登録する</button>
    </form>

    <div class="faq-list">
        <h3>登録されたFAQ</h3>
        <div v-for="faq in faqs" :key="faq.id" class="faq-item">
            <div><strong>質問:</strong> {{ faq.Q }}</div>
            <div><strong>回答:</strong> {{ faq.A }}</div>
            <button @click="editFAQ(faq)">編集</button>
            <button @click="deleteFAQ(faq.id)">削除</button>
        </div>
    </div>

    <p v-if="statusMessage">{{ statusMessage }}</p>
</div>

<script>
const { createApp } = Vue;
createApp({
    data() {
        return {
            question: '',
            answer: '',
            faqs: [],
            statusMessage: '',
            editingFAQ: null,
        };
    },
    mounted() {
        this.fetchFAQs();
    },
    methods: {
        async fetchFAQs() {
            try {
                const response = await fetch('https://us-central1-manchi-d02a5.cloudfunctions.net/getFAQs');
                const data = await response.json();
                this.faqs = data.faqs;
            } catch (error) {
                this.statusMessage = 'FAQの取得に失敗しました。';
            }
        },
        async submitForm() {
            const faqData = {
                Q: this.question,
                A: this.answer
            };

            try {
                let url = 'https://us-central1-manchi-d02a5.cloudfunctions.net/addFAQ';
                let method = 'POST';

                if (this.editingFAQ) {
                    url = 'https://us-central1-manchi-d02a5.cloudfunctions.net/updateFAQ';
                    faqData.id = this.editingFAQ.id;
                    method = 'PUT';
                }

                await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(faqData)
                });

                this.statusMessage = 'FAQが正常に登録されました！';
                this.fetchFAQs();  // 更新後に再取得
                this.resetForm();
            } catch (error) {
                this.statusMessage = 'エラーが発生しました。再度お試しください。';
            }
        },
        editFAQ(faq) {
            this.question = faq.Q;
            this.answer = faq.A;
            this.editingFAQ = faq;
        },
        async deleteFAQ(id) {
            try {
                await fetch(`https://us-central1-manchi-d02a5.cloudfunctions.net/deleteFAQ/${id}`, { method: 'DELETE' });
                this.statusMessage = 'FAQが削除されました！';
                this.fetchFAQs();  // 更新後に再取得
            } catch (error) {
                this.statusMessage = '削除に失敗しました。';
            }
        },
        resetForm() {
            this.question = '';
            this.answer = '';
            this.editingFAQ = null;
        }
    }
}).mount('#faq-form');
</script>

</body>
</html>
